import { readFileSync } from 'node:fs'
import { withMermaid } from 'vitepress-plugin-mermaid'

const { version } = JSON.parse(readFileSync(new URL('../package.json', import.meta.url), 'utf8'))

// Lowercase, hex-hashed asset names: the host app redirects any URL containing uppercase.
// Client build only; applying it to the SSR build breaks it.
const lowercaseAssets = {
  name: 'lowercase-assets',
  config(config, { isSsrBuild }) {
    if (isSsrBuild) return
    return { build: { rollupOptions: { output: {
      hashCharacters: 'hex',
      entryFileNames: (c) => `assets/${c.name.toLowerCase()}.[hash].js`,
      chunkFileNames: (c) => `assets/chunks/${c.name.toLowerCase()}.[hash].js`,
      assetFileNames: (a) => `assets/${(a.names?.[0] ?? 'asset').toLowerCase().replace(/\.[^.]+$/, '')}.[hash][extname]`,
    } } } }
  },
}

const page = (text, slug) => ({ text, link: `/${slug}` })

export default withMermaid({
  title: 'Drupal Backend',
  description: 'Developer documentation for proteo/drupal-backend.',
  base: '/backend-docs/',
  cleanUrls: true,
  outDir: '../public',
  appearance: true,
  ignoreDeadLinks: false,
  // Pages index concurrently otherwise, so search document ids (and every chunk hash) change per build.
  buildConcurrency: 1,
  srcExclude: ['node_modules/**', 'snippets/**', 'validating-the-backend.md'],
  vite: { plugins: [lowercaseAssets] },
  themeConfig: {
    search: { provider: 'local' },
    footer: { message: `proteo/drupal-backend ${version}` },
    sidebar: [
      { text: 'Start', items: [{ text: 'Introduction', link: '/' }, page('Vocabulary', 'vocabulary'), page('Quick start', 'quick-start')] },
      { text: 'Foundations', items: [page('Architecture', 'architecture'), page('Caching model', 'caching-model'), page('Installation', 'installation')] },
      { text: 'Drupal', items: [page('Backend setup', 'backend-setup'), page('Webhook and cache flushing', 'webhook-and-cache-flushing')] },
      { text: 'Configure', items: [page('Configuration reference', 'configuration-reference'), page('Descriptor reference', 'descriptor-reference')] },
      { text: 'Build', items: [page('Mapping content', 'mapping-content'), page('Routes and controllers', 'routes-and-controllers'), page('Views and overrides', 'views-and-overrides'), page('Related content', 'related-content'), page('Entity decorators', 'entity-decorators'), page('Multiple Drupal sources', 'multiple-drupal-sources')] },
      { text: 'Search', items: [page('Search setup', 'search-setup'), page('Search suggestions', 'search-suggestions'), page('Index extension', 'index-extension')] },
      { text: 'Operate', items: [page('Artisan commands', 'artisan-commands'), page('Testing', 'testing'), page('Validation in CI/deploy', 'validation-in-ci-deploy'), page('Troubleshooting', 'troubleshooting'), page('Upgrading', 'upgrading')] },
      { text: 'Decisions', items: [page('ADR index', 'adr/')] },
    ],
  },
})
