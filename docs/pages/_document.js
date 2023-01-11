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

.code-selector {
  overflow-x: hidden;
  margin: 32px 0;
  padding-bottom: 16px;
  border-bottom: 1px solid #ccc;
}

.code-selector input {
  display: none;
}

.code-selector .code-selector-nav {
  display: flex;
  align-items: stretch;
  list-style: none;
  padding: 0;
  border-bottom: 1px solid #ccc;
}

.code-selector label {
  display: block;
  margin-bottom: -1px;
  padding: 12px 15px;
  border: 1px solid #ccc;
  background: #eee;
  color: #666;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
  cursor: pointer;
  transition: all 0.3s;
}

.code-selector label:hover {
  border-top-color: #333;
  color: #333;
}

.code-selector .code-selector-content {
  display: none;
  color: #777;
}

.code-selector input:nth-of-type(1):checked ~ .code-selector-nav label:nth-of-type(1),
.code-selector input:nth-of-type(2):checked ~ .code-selector-nav label:nth-of-type(2),
.code-selector input:nth-of-type(3):checked ~ .code-selector-nav label:nth-of-type(3),
.code-selector input:nth-of-type(4):checked ~ .code-selector-nav label:nth-of-type(4),
.code-selector input:nth-of-type(5):checked ~ .code-selector-nav label:nth-of-type(5) {
  border-bottom-color: #fff;
  border-top-color: #B721FF;
  background: #fff;
  color: #222;
}

.code-selector input:nth-of-type(1):checked ~ .code-selector-content:nth-of-type(1),
.code-selector input:nth-of-type(2):checked ~ .code-selector-content:nth-of-type(2),
.code-selector input:nth-of-type(3):checked ~ .code-selector-content:nth-of-type(3),
.code-selector input:nth-of-type(4):checked ~ .code-selector-content:nth-of-type(4),
.code-selector input:nth-of-type(5):checked ~ .code-selector-content:nth-of-type(5) {
  display: block !important;
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
