import { Html, Head, Main, NextScript } from 'next/document'
import Sidebar from './sidebar.mdx'

export default function Document() {
  const style = `
.container {
  box-sizing: border-box;
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
  border-right: 1px solid grey;
  margin-right: 20px;
}

.main {
  flex: 3;
}
.row {
  display: flex;
  margin: auto -1rem 1rem;
}

ul.sections {
  list-style-type: none;
  padding: 0;
  margin: 0;
}

.sections li {
	display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  width: 100%;
  margin: 0 !important;
}

.sections .annotation {
  flex: 1;
}

.sections .content {
	display: flex;
  background: #f6f8fa;
  flex: 1;
  width: 70%;
}

.sections .content pre {
  border: 0;
  margin: 0;
  width: 100%;
}

.sswrap {
  float: left;
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
        <div class="container">
          <div class="row">
            <nav class="markdown-body sidebar">
              <Sidebar />
            </nav>
            <article class="markdown-body main ">
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
