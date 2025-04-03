---
title: Modern Open Journal Systems (MOJS)
---

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Modern Open Journal Systems (MOJS)</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- React CDNs -->
  <script src="https://unpkg.com/react@18/umd/react.development.js"></script>
  <script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-6">
  <header class="text-center mb-12">
    <h1 class="text-5xl font-bold text-blue-600 mb-4">Modern Open Journal Systems (MOJS)</h1>
    <p class="text-xl text-gray-700 max-w-2xl">
      An open-source evolution of scholarly publishing, reimagining OJS with a modern stack.
    </p>
  </header>

  <main class="bg-white shadow-lg rounded-lg p-8 max-w-3xl w-full">
    <p class="text-lg text-gray-600 mb-6">
      MOJS is a fork of 
      <a href="https://pkp.sfu.ca/software/ojs/" class="text-blue-500 hover:underline">Open Journal Systems (OJS)</a>, 
      originally developed by the 
      <a href="https://pkp.sfu.ca/" class="text-blue-500 hover:underline">Public Knowledge Project (PKP)</a>. 
      Maintained by 
      <a href="https://website.anestesiudayana.com/" class="text-blue-500 hover:underline">Balinesthesia</a>, 
      we’re building with React, Rust, and Python for features like peer review matchmaking.
    </p>
    <p class="text-md text-gray-500 italic">
      In early development as of April 2025—join us to shape its future!
    </p>
    <!-- React Container -->
    <div id="react-root" class="mt-4"></div>
  </main>

  <section class="mt-12 grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-3xl w-full">
    <a href="https://github.com/balinesthesia/modern-ojs" class="bg-blue-500 text-white text-center py-3 rounded-lg hover:bg-blue-600 transition">
      Source Code
    </a>
    <a href="https://balinesthesia.github.io/modern-ojs/docs/" class="bg-green-500 text-white text-center py-3 rounded-lg hover:bg-green-600 transition">
      Documentation (Coming Soon)
    </a>
    <a href="https://github.com/balinesthesia/modern-ojs/discussions" class="bg-purple-500 text-white text-center py-3 rounded-lg hover:bg-purple-600 transition">
      Discussions
    </a>
    <a href="https://github.com/balinesthesia/modern-ojs/issues" class="bg-red-500 text-white text-center py-3 rounded-lg hover:bg-red-600 transition">
      Issues
    </a>
  </section>

  <footer class="mt-12 text-gray-500 text-sm">
    <p>Stay tuned for our first release!</p>
  </footer>

  <!-- React Script -->
  <script type="text/javascript">
    const { useState } = React;
    const App = () => {
      const [count, setCount] = useState(0);
      return React.createElement(
        'div',
        { className: 'text-center' },
        React.createElement('p', null, `Clicked ${count} times`),
        React.createElement(
          'button',
          {
            className: 'bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600',
            onClick: () => setCount(count + 1),
          },
          'Click Me'
        )
      );
    };
    const root = ReactDOM.createRoot(document.getElementById('react-root'));
    root.render(React.createElement(App));
  </script>
</body>
</html>