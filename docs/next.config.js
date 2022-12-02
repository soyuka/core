const withMDX = require('@next/mdx')({
  extension: /\.mdx?$/,
  options: {
    // If you use remark-gfm, you'll need to use next.config.mjs
    // as the package is ESM only
    // https://github.com/remarkjs/remark-gfm#install
    remarkPlugins: [
      [
        require("remark-prism"), 
        // https://www.npmjs.com/package/remark-prism
        // [
        //     'autolinker',
        //     'command-line',
        //     'data-uri-highlight',
        //     'diff-highlight',
        //     'inline-color',
        //     'keep-markup',
        //     'line-numbers',
        //     'show-invisibles',
        //     'treeview',
        // ]
      ],
    ],
    rehypePlugins: [],
    // If you use `MDXProvider`, uncomment the following line.
    // providerImportSource: "@mdx-js/react",
  },
})
module.exports = withMDX({
  // Append the default value with md extensions
  pageExtensions: ['ts', 'tsx', 'js', 'jsx', 'md', 'mdx'],
  experimental: {
    mdxRs: true
  }
})
// todo: https://github.com/remarkjs/remark/discussions/965
