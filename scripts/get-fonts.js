/**
 * Download and store Google font data
 *
 * This script downloads and stores font embed data
 * for all Google fonts listed in the Google Font API.
 *
 * The correct font file URLs are only provided in response to a
 * browser request, so this script mocks a browser that will
 * return the WOFF2 format.
 *
 * This script creates a JSON file with all of the font options.
 * It also stores separate JSON files with the URL to the Woff2
 * files along with the corresponding @font-face declarations
 * needed to load each subset of the font.
 *
 * @see https://developers.google.com/fonts/docs/developer_api
 */
import fs from 'fs'
import 'dotenv/config'

const queryParams = {
  key: process.env.GOOGLE_FONTS_API_KEY,
  capability: 'VF',
}

const Q_ITALICS = 'ital'
const Q_WEIGHT = 'wght'
const API_URL = 'https://www.googleapis.com/webfonts/v1/webfonts'
const EMBED_URL = 'https://fonts.googleapis.com/css2'

const EXCLUDED = [
  'Linefont',
]

const FONT_LIST_FILE = './fonts/fonts.json'

// Fake a browser user agent so that the Google embed API
// will return code pointing to .woff2 files
const BROWSER_HEADERS = {
  'Accept': '*/*',
  'Connection': 'keep-alive',
  'User-Agent': 'Mozilla/5.0 (Windows NT 6.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.110 Safari/537.36',
  'Accept-Language': 'en-US;q=0.5,en;q=0.3',
  'Cache-Control': 'max-age=0',
  'Upgrade-Insecure-Requests': '1',
}

/**
 * Run the script
 *
 * Gets URLs for all font files that have been updated since the
 * last time this script was run and saves the FONT_LIST_FILE
 * configuration object.
 */
const run = async () => {
  const fonts = await getFontList(getApiUrl(queryParams))
  const oldFonts = JSON.parse(fs.readFileSync(FONT_LIST_FILE, 'utf-8'))

  const fontsToUpdate = getFontsToUpdate(oldFonts, fonts)

  console.log(`Found ${fontsToUpdate.length} fonts updated since last run.`)

  let saved = []
  for (const font of fontsToUpdate) {
    const result = await saveFont(font)
    if (result) {
      saved.push(font)
    }
  }

  fs.writeFileSync(FONT_LIST_FILE, JSON.stringify(fonts))

  console.log(`${saved.length}/${fontsToUpdate.length} fonts updated successfully.`)
}

/**
 * Get the URL to the Google Fonts API
 */
const getApiUrl = (params) => {
  const q = new URLSearchParams(params);
  return `${API_URL}?${(q.toString())}`
}

/**
 * Get full list of fonts, along with the woff2
 * file for displaying a sample of the font.
 */
const getFontList = async (url) => {
  return fetch(url)
    .then(r => r.json())
    .then(data => data.items)
    .then(fonts => fonts.filter(font => !EXCLUDED.includes(font.family)))
    .then(fonts => fonts.map(font => {
        const { family, variants, subsets, version, lastModified, category, axes} = font
        return { family, variants, subsets, version, lastModified, category, axes}
      })
    )
    .then(fonts => {
      return fetch(getApiUrl({...queryParams, capability: 'WOFF2'}))
        .then(r => r.json())
        .then(data => data.items)
        .then(woffFonts => {
          return fonts.map(font => {
            const woffFont = woffFonts.find(woffFont => woffFont.family === font.family)
            return {
              ...font,
              menu: woffFont.menu ?? font.menu
            }
          })
        })
    })
}

/**
 * Download fonts and save font data
 */
const saveFont = async (font) => {
  console.log(`Updating ${font.family}`)
  const embedUrl = getFontEmbedUrl(font)
  const subsets = await getFontSubsets(embedUrl)
  if (!subsets[0]?.font) {
    console.log('no subsets?', font, subsets)
    throw new Error('No subsets?')
  }
  const slug = getFamilyDir(subsets[0]?.font)

  if (!slug) {
    throw new Error(`Unable to get slug for ${font.family}`)
  }

  font.id = slug

  const dir = `./fonts/${slug}`
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir)
  }

  fs.readdirSync(dir).forEach(file => fs.unlinkSync(`${dir}/${file}`))

  const localSubsets = getLocalFontFace(subsets, dir)

  fs.writeFileSync(`${dir}/embed.json`, JSON.stringify(localSubsets))

  const urls = subsets.map(subset => {
    const url = getDownloadUrl(subset.font)
    if (!url) {
      throw new Error(`Unable to find download URL for ${font.family} in ${subset.font}`)
    }
    return url
  })

  fs.writeFileSync(`${dir}/urls.json`, JSON.stringify(urls))

  return true
}

/**
 * Extract list of @font-face declarations by their
 * subset (eg - latin)
 */
const getFontSubsets = async (embedUrl) => {
  return fetch(embedUrl, {headers: BROWSER_HEADERS})
    .then(r => r.text())
    .then(embed => {
      const subsets = []
      let subset = ''
      let fontLines = []
      embed
        .split('\n')
        .forEach(line => {
          const titleMatch = [...line.matchAll(/\/\*\s([a-z0-9\-\[\]]*)/gi)]

          // Starting a new subset
          if (titleMatch.length) {
            if (subset) {
              subsets.push({
                subset,
                font: fontLines.join('\n'),
              })
            }
            subset = titleMatch[0][1]
            fontLines = []

          // Collect @font-face declaration
          } else {
            fontLines.push(line)
          }
        })
      if (subset) {
        subsets.push({
          subset,
          font: fontLines.join('\n'),
        })
      }
      return subsets
    })
}

/**
 * Get a URL that returns the @font-face declarations for
 * all weights and subsets.
 */
const getFontEmbedUrl = font => {
  const familyUrlParam = font.family.replace(/ /g, '+')
  const weightUrlParam = !font?.axes?.length
    ? getStaticWeightsUrlFragment(font.variants)
    : getVariableWeightsUrlFragment(font.variants, font.axes)
  const params = [familyUrlParam, weightUrlParam].filter(p => p)
  return `${EMBED_URL}?family=${params.join(':')}&display=swap`
}

/**
 * Get the embed URL fragment for all weights of a static font
 */
const getStaticWeightsUrlFragment = variants => {
  const regular = []
  const italic = []
  variants.forEach(variant => {
    if (variant === 'regular') {
      regular.push(400)
    } else if (variant === 'italic') {
      italic.push(400)
    } else if (variant.includes('italic')) {
      const matches = variant.match(/(\d+)/)
      if (matches) {
        italic.push(parseInt(matches[0]))
      } else {
        console.log(`Unrecognized variant ${variant} in ${font.family}`)
      }
    } else {
      regular.push(parseInt(variant))
    }
    return variant
  })
  if (!italic.length) {
    return `${Q_WEIGHT}@${regular.join(';')}`
  }
  const weights = [
    ...regular.map(w => `0,${w}`),
    ...italic.map(w => `1,${w}`),
  ]
  return `${Q_ITALICS},${Q_WEIGHT}@${weights.join(';')}`
}

/**
 * Get the embed URL fragment for all weights of a variable font
 *
 * A few fonts have no weight variable (eg - Ballet). We don't
 * track any other axes for variable fonts yet.
 */
const getVariableWeightsUrlFragment = (variants, axes) => {
  const weight = axes?.find(axe => axe.tag === Q_WEIGHT)
  if (!weight) {
    return ''
  }
  const weightRange = `${weight.start}..${weight.end}`
  if (!variants.includes('italic')) {
    return `${Q_WEIGHT}@${weightRange}`
  }
  return `${Q_ITALICS},${Q_WEIGHT}@0,${weightRange};1,${weightRange}`
}

/**
 * Get the directory of the font files from a @font-face block
 */
const getFamilyDir = fontFace => {
  const matches = [...fontFace.match(/https:\/\/fonts.gstatic.com\/s\/([^\/]*)/i)]
  if (matches.length) {
    return matches[1]
  }
  return ''
}

/**
 * Extract the download URL from a @font-face block
 */
const getDownloadUrl = fontFace => {
  const matches = [...fontFace.match(/(https:\/\/fonts.gstatic.com\/s\/[^\)]*)/i)]
  if (matches.length) {
    return matches[0]
  }
  return ''
}

/**
 * Get the @font-face block with the URL replaced by a placeholder
 */
const getLocalFontFace = (subsets, dir) => {
  return subsets.map(subset => {
    return {
      ...subset,
      font: subset.font.replace(/(https:\/\/fonts.gstatic.com\/s\/[^\/]*\/[^\/]*\/)/i, `${dir}/`)
    }
  })
}

/**
 * Compare the existing font list with a new font list
 * and return the files that have new changes
 */
const getFontsToUpdate = (oldFonts, newFonts) => {
  return newFonts
    .filter(font => {
      const oldFont = oldFonts.find(f => f.family === font.family)
      if (!oldFont) {
        return true;
      }
      const newLastModified = new Date(font.lastModified)
      const oldLastModified = new Date(oldFont.lastModified)
      if (newLastModified.getTime() > oldLastModified.getTime()) {
        return true
      }
      return false
    })
}

run()