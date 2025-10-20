export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  theme: { extend: {} },
  plugins: [
    import('@tailwindcss/forms').then(m => m.default()),
  ],
};
