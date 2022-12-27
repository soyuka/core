import remarkFrontmatter from 'remark-frontmatter' // YAML and such.
import remarkFrontmatterMdx from 'remark-mdx-frontmatter' // YAML and such.
import rehypePrism from '@mapbox/rehype-prism'
import mdx from '@next/mdx'

const withMDX = mdx({
  extension: /\.mdx?$/,
  options: {
    // If you use remark-gfm, you'll need to use next.config.mjs
    // as the package is ESM only
    // https://github.com/remarkjs/remark-gfm#install
    remarkPlugins: [
      remarkFrontmatter,
      remarkFrontmatterMdx
    ],
    rehypePlugins: [
      rehypePrism
    ],
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
