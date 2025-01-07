<!DOCTYPE html>
<html lang="eo" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="format-detection" content="telephone=no,date=no,address=no,email=no,url=no">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>{{ subject }}</title>
    <!--[if mso]>
      <noscript>
        <xml>
          <o:OfficeDocumentSettings>
            <o:PixelsPerInch>96</o:PixelsPerInch>
          </o:OfficeDocumentSettings>
        </xml>
      </noscript>
      <![endif]-->
    <style>
      :root {
        color-scheme: light dark;
        supported-color-schemes: light dark;
      }

      .body {
        background:#f5f5f5;
        color: #190038;

        -moz-osx-font-smoothing: grayscale;
        -webkit-font-smoothing: antialiased;
        text-rendering: optimizeLegibility;
        font-family: BlinkMacSystemFont, -apple-system, "Helvetica Neue",
        "Helvetica", "Arial", sans-serif;
        font-size: 16px;
        font-size: 1rem;
        font-size: max(16px, 1rem);
        line-height: 1.3;
        padding: 10px;
      }

      .article {
        max-width: 37.5em;
        margin: 0 auto;
        mso-element-frame-width: 37.5em;
        mso-element: para-border-div;
        mso-element-left: center;
        mso-element-wrap: no-wrap-beside;
      }

      .container {
        background: #fff;
        border-radius: 0.375rem;
      }

      .header {
        padding: 30px;
        text-align: center;
      }

      .content {
        padding: 0 30px 30px;
        word-wrap: break-word;
      }

      .footer {
        color: #786f84;
        padding: 30px;
        text-align: center;
        font-size: 80%;
      }

      .h1 {
        font-size: 24px;
        font-weight: bold;
        margin: 0 0 1.2em 0;
      }

      .p {
        line-height: 1.6;
        margin: 0 0 1.2em 0;
      }

      .a,
      a:link,
      a:visited,
      a:hover,
      a:active {
        color: #00798a;
        font-weight: bold;
        text-decoration: none;
      }

      .img {
        image-rendering: -webkit-optimize-contrast;
        image-rendering: crisp-edges;
      }

      @media (prefers-color-scheme: dark) {
        .body {
          background: #1f1f1f;
          color: #fff;
        }

        .container {
          background: #1f1f1f;
        }

        .footer {
          color: #928c9a;
        }

        .a,
        a:link,
        a:visited,
        a:hover,
        a:active {
          color: #4dabbd;
        }
      }
    </style>
  </head>
  <body class="body">
    <article class="article" role="article" lang="eo" aria-roledescription="email" aria-label="{{ subject }}">
      <div class="container">
        <header class="header">
          <a href="https://volontulo.net">
            <picture>
              <source srcset="/img/logo.png" media="(prefers-color-scheme: light)">
              <source srcset="/img/logo-white.png"  media="(prefers-color-scheme: dark)">
              <img src="/img/logo.png" alt="volontulo" width="135">
            </picture>
          </a>
        </header>
        <div class="content">

<h1 class="h1">Bonvenon, amiko!</h1>

<p class="p">Ni sendis al vi ĉi tiun mesaĝon ĉar vi registriĝis ĉe volontulo.net.
Alklaku la ligilon por fini la registriĝon kaj aliri ĉiujn funkciojn de la retejo.
La ligilo validas nur dum 20 minutoj.</p>

<p class="p"><a class="confirm-link" href="https://volontulo.net/konto/konfirmi/{{ token }}" 
    title="Konfirmi registriĝon">https://volontulo.net/konto/konfirmi/{{ token }}</a></p>

<p class="p">Se vi ne petis ĉi tiun mesaĝon, vi povas ignori ĝin. Ni pardonpeta
    pro la ĝeno.</p>

</div>
      </div>
      <footer class="footer">
        <p>&copy;2025 Volontulo</p>
      </footer>
    </article>
  </body>
</html>
