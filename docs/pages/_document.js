import { Html, Head, Main, NextScript } from 'next/document'
import Sidebar from './sidebar.mdx'

export default function Document() {
  const style = `
.container {
  box-sizing: norder-box;
  min-width: 200px;
  margin: 0 auto;
  padding: 25px;
}

@media (max-width: 767px) {
  .container {
    padding: 15px;
  }
}

.sidebar {
  flex: 1;
  max-width: 250px;
  padding: 20px;
  border-right: 1px solid #d7dde3;
  margin-right: 20px;
}

.main {
  flex: 3;
  margin-left: 20px;
}
.row {
  display: flex;
  margin: auto -1rem 1rem;
}

.sections .section {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  width: 100%;
}

.sections .section .annotation, .sections .section .content {
  display: flex;
  flex-direction: column;
  flex-basis: 100%;
  flex: 1;
}

.sections .section .annotation {
}

.sections .section .content {
  width: 40%;
}

.sections .section .content pre {
  border: 0;
  margin-top: 0;
  padding-top: 0;
}

.sections code[class*="language-"], pre[class*="language-"] {
    white-space: normal !important;
    word-break: break-word !important;
}
`
  return (
    <Html>
      <Head>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/5.1.0/github-markdown-light.min.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism-themes/1.9.0/prism-ghcolors.min.css"></link>
        <style>{style}</style>
      </Head>
      <body>
        <div className="container">
          <div className="row">
            <nav className="markdown-body sidebar">
              <Sidebar />
            </nav>
            <article className="markdown-body main ">
              <Main />
            </article>
          </div>
        </div>
        <NextScript />
        <script type="text/javascript" src="/prism-php.js"></script>
      </body>
    </Html>
  )
}
