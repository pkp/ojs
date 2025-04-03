/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./index.html",
        "./src/**/*.{js,ts,jsx,tsx}",
    ],
    theme: {
        extend: {
            colors: {
                primary: '#3B82F6', // blue-500
                secondary: '#10B981', // green-500
                accent: '#8B5CF6', // purple-500
                danger: '#EF4444', // red-500
            },
        },
    },
    plugins: [],
} 