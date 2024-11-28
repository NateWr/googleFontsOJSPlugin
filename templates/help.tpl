<!doctype html>
<html lang="{$currentLocale|replace:"_":"-"}" xml:lang="{$currentLocale|replace:"_":"-"}">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>{translate key="plugins.generic.googleFonts.help.title"}</title>
  <style>
    html,
    body {
      margin: 0;
      padding: 0;
      font-family: system-ui, sans-serif;
      line-height: 1.5;
    }
    .wrapper {
      display: flex;
      justify-content: center;
      align-items: center;
      width: 100%;
      padding: 1.5rem;
      background: #ddd;
      box-sizing: border-box;
    }
    .main {
      width: 100%;
      padding: 1rem;
      background: white;
      color: black;
      border-radius: 0.25rem;
    }
    h1,
    h2 {
      line-height: 1.15;
    }
    h2 {
      margin-top: 4rem;
    }
    .google-fonts-help-code {
      padding: 1.5rem;
      font-size: 1rem;
      background: #eee;
      overflow: auto;
    }
    @media (min-width: 767px) {
      .main {
        max-width: 32rem;
      }
    }
  </style>
</head>

<body dir="{$currentLocaleLangDir|escape|default:"ltr"}">

  <div class="wrapper">
    <main class="main">
      <h1>
        {translate key="plugins.generic.googleFonts.help.title"}
      </h1>
      <p>
        {translate
          key="plugins.generic.googleFonts.help.about"
          googleFontsUrl="https://fonts.google.com"
        }
      </p>
      <h2 id="not-visible">
        {translate key="plugins.generic.googleFonts.help.fontsNotShown"}
      </h2>
      <p>
        {translate key="plugins.generic.googleFonts.help.fontsNotShown.answer"}
      </p>
      <p>
        {translate key="plugins.generic.googleFonts.help.fontsNotShown.example"}
      </p>
  <pre class="google-fonts-help-code">
body {
  font-family: 'Urbanist', system-ui, sans-serif;
}</pre>
      <p>
        {translate key="plugins.generic.googleFonts.help.fontsNotShown.contactAdmin"}
      </p>
      <h2 id="too-many-fonts">
        {translate key="plugins.generic.googleFonts.help.tooManyFonts"}
      </h2>
      <p>
        {translate key="plugins.generic.googleFonts.help.tooManyFonts.answer"}
      </p>
      <h2 id="gdpr">
        {translate key="plugins.generic.googleFonts.help.gdpr"}
      </h2>
      <p>
        {translate key="plugins.generic.googleFonts.help.gdpr.answer"}
      </p>
      <p>
        {translate
          key="plugins.generic.googleFonts.help.gdpr.compliance"
          gdprUrl="https://en.wikipedia.org/wiki/General_Data_Protection_Regulation"
        }
      </p>
      <h2 id="license">
        {translate key="plugins.generic.googleFonts.help.license"}
      </h2>
      <p>
        {translate
          key="plugins.generic.googleFonts.help.license.answer"
          silUrl="https://openfontlicense.org/"
        }
      </p>
    </main>
  </div>
</body>
</html>