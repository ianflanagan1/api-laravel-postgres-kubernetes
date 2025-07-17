export default {
  // Prettier - Check changed files
  '**/*.{js,jsx,mjs,cjs,ts,tsx,mts,cts,vue,css,scss,less,json,md,mdx,yml,yaml,html,htm,toml}':
    'prettier --write',

  // ESLint - Check changed files
  '**/*.{js,jsx,mjs,cjs,ts,tsx,mts,cts,vue}': 'eslint --cache',

  // Vue TSC - If at least one file has changed, check the whole vue codebase
  '**/*.{ts,tsx,mts,cts,vue}': () => 'vue-tsc --build --noEmit',

  // Vitest - Check tests related to changed files
  // Note: For CI/CD Pipeline, run all tests
  '**/*.{js,jsx,mjs,cjs,ts,tsx,mts,cts,vue}': 'vitest --run related',
}
