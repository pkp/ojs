// http://eslint.org/docs/user-guide/configuring

module.exports = {
  root: true,
  parser: 'babel-eslint',
  parserOptions: {
    sourceType: 'module'
  },
  env: {
    browser: true,
  },
  // https://github.com/feross/standard/blob/master/RULES.md#javascript-standard-style
  extends: 'standard',
  // required to lint *.vue files
  plugins: [
    'html'
  ],
  globals: {
    '_': false,
    '$': false,
    'pkp': false,
  },
  // add your custom rules here
  'rules': {
    // allow paren-less arrow functions
    'arrow-parens': 0,
    // allow async-await
    'generator-star-spacing': 0,
    // allow debugger during development
    'no-debugger': process.env.NODE_ENV === 'production' ? 2 : 0,
    // use tab indentation
    'no-tabs': 0,
    'indent': ['error', 'tab', {SwitchCase: 1}],
    // enforce trailing commas on multi-line arrays/obbjects
    'comma-dangle': ['error', 'always-multiline'],
    // require semi-colons at the end of statements
    'semi': ['error', 'always'],
    // don't enforce block padding rules
    'padded-blocks': 0,
    // don't require === for comparisons
    'eqeqeq': 0,
  }
};
