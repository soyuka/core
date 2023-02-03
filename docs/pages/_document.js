import { Html, Head, Main, NextScript } from 'next/document'
import Sidebar from './sidebar.mdx'

export default function Document() {
  const style = `
.container {
  box-sizing: norder-box;
  min-width: 200px;
  margin: 0 auto;
  padding: 20px;
}

@media (max-width: 767px) {
  .container {
    padding: 15px;
  }
}

.sidebar {
  flex: 1;
  max-width: 300px;
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

.api-list-container {
  display: flex;
  flex-direction: column;
  padding: 16px 0;
  position: relative;
}

.api-list-container h2 {
  margin-top: 16px;
  margin-bottom: 16px;
}

.api-list-container ul.api-list {
  list-style: none;
  margin: 0 0 32px -8px;
  padding: 0;
  overflow: hidden;
}

.api-list-container ul.api-list li.api-item {
  font-size: 1.4rem;
  margin: 8px 0;
  line-height: 14px;
  line-height: 1.4rem;
  padding: 0;
  float: left;
  width: 33%;
  overflow: hidden;
  min-width: 330px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.api-list-container .symbol {
  box-shadow: 0 1px 2px #0a10143d;
  color: #fff;
  border-radius: 2px;
  display: inline-block;
  font-size: 10px;
  font-size: 1rem;
  font-weight: 600;
  line-height: 16px;
  line-height: 1.6rem;
  margin-right: 8px;
  text-align: center;
  width: 16px;
}

.symbol.T {
  background: #4CAF50;
}

.symbol.I {
  background: #009688;
}

.symbol.C {
  background: #2196F3;
}

.symbol.A {
  background: #FFA000;
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
      </body>
    </Html>
  )
}
