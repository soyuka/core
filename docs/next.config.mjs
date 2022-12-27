import remarkFrontmatter from 'remark-frontmatter' // YAML and such.
import remarkFrontmatterMdx from 'remark-mdx-frontmatter' // YAML and such.
import remarkPrism from 'remark-prism'
import remarkDisableBlocks from 'remark-disable-tokenizers'
import mdx from '@next/mdx'

const withMDX = mdx({
  extension: /\.mdx?$/,
  options: {
    // If you use remark-gfm, you'll need to use next.config.mjs
    // as the package is ESM only
    // https://github.com/remarkjs/remark-gfm#install
    remarkPlugins: [
      remarkFrontmatter,
      remarkFrontmatterMdx,
      [
        remarkPrism,
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

const nextConfig = withMDX({
  // Append the default value with md extensions
  pageExtensions: ['ts', 'tsx', 'js', 'jsx', 'md', 'mdx'],
  // experimental: {
  //   mdxRs: true
  // }
})

export default nextConfig
// todo: https://github.com/remarkjs/remark/discussions/965
