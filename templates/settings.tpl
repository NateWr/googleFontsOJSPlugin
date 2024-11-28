{**
 * Settings form to manage Google Fonts
 *}
<tab id="google-fonts" label="Google Fonts">
  <div class="google-fonts-settings">
    {if $googleFontsEnabled|@count > 2}
      <div class="pkpNotification pkpNotification--warning">
        {translate
          key="plugins.generic.googleFonts.fontCountWarning"
          url="{url page="google-font" op="help" anchor="too-many-fonts"}"
        }
      </div>
    {/if}
    <div class="google-fonts-settings-section">
      <div class="google-fonts-settings-section-header">
        <h2 class="google-fonts-settings-title">
          {translate key="plugins.generic.googleFonts.fonts"}
        </h2>
        {if !$googleFontsEnabled|@count}
          <div>
            {translate key="plugins.generic.googleFonts.noFontsEnabled"}
          </div>
        {/if}
      </div>
      {if $googleFontsEnabled|@count}
        <ul class="google-fonts-settings-list">
          {foreach from=$googleFontsEnabled item="font"}
            <li>
              <span class="google-fonts-settings-font-title">
                {$font->family}
                <a
                  href="https://fonts.google.com/specimen/{$font->family|escape|replace:' ':'+'}"
                  target="_blank"
                >
                  <span class="pkp_screen_reader">
                    {translate
                      key="common.viewWithName"
                      name="{$font->family}"
                    }
                  </span>
                  <svg
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    height="24px"
                    viewBox="0 -960 960 960"
                    width="24px"
                    fill="currentColor"
                  >
                    <path d="M224.62-160q-27.62 0-46.12-18.5Q160-197 160-224.62v-510.76q0-27.62 18.5-46.12Q197-800 224.62-800h224.61v40H224.62q-9.24 0-16.93 7.69-7.69 7.69-7.69 16.93v510.76q0 9.24 7.69 16.93 7.69 7.69 16.93 7.69h510.76q9.24 0 16.93-7.69 7.69-7.69 7.69-16.93v-224.61h40v224.61q0 27.62-18.5 46.12Q763-160 735.38-160H224.62Zm164.92-201.23-28.31-28.31L731.69-760H560v-40h240v240h-40v-171.69L389.54-361.23Z"/>
                  </svg>
                </a>
              </span>

              <form
                action={url page="google-font" op="remove"}
                method="post"
              >
                {csrf}
                <input type="hidden" name="font" value="{$font->id}">
                <button type="submit" class="pkpButton">
                  <span class="aria-hidden">
                    {translate key="common.remove"}
                  </span>
                  <span class="pkp_screen_reader">
                    {translate key="common.removeItem" item=$font->family}
                  </span>
                </button>
              </form>
            </li>
          {/foreach}
        </ul>
        <div>
          {translate
            key="plugins.generic.googleFonts.howToUse"
            url="{url page="google-font" op="help" anchor="not-visible"}"
          }
        </div>
      {/if}
    </div>
    <div class="google-fonts-settings-section">
      <div class="google-fonts-settings-section-header">
        <h2 class="google-fonts-settings-title">
          {translate key="plugins.generic.googleFonts.addFont"}
        </h2>
        <div>
          {translate
            key="plugins.generic.googleFonts.addFont.description"
            url="https://fonts.google.com"
          }
        </div>
      </div>
      {if $googleFontsOptions|@count}
        <form
          action={url page="google-font" op="add"}
          class="google-fonts-settings-add-form"
          method="post"
        >
          {csrf}
          <label
            class="pkp_screen_reader"
            for="google-font-select-font"
          >
            {translate key="plugins.generic.googleFonts.selectFont"}
          </label>
          <select
            class="pkpFormField__input"
            id="google-font-select-font"
            name="font"
          >
            {foreach from=$googleFontsOptions item="googleFontsOption"}
              <option value="{$googleFontsOption->id|escape}">
                {$googleFontsOption->family|escape}
              </option>
            {/foreach}
          </select>
          <button
            class="pkpButton"
            type="submit"
          >
            {translate key="plugins.generic.googleFonts.addFont"}
          </button>
        </form>
      {elseif $googleFontsError}
        <div class="pkpNotification pkpNotification--warning">
          {$googleFontsError}
        </div>
      {/if}
    </div>
  </div>
</tab>