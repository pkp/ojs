import React from 'react';
import { Counter } from './components/Counter';

const App: React.FC = () => {
    return (
        <div className="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8 flex flex-col items-center">
            <header className="text-center mb-12">
                <h1 className="text-5xl font-bold text-primary mb-4">Modern Open Journal Systems (MOJS)</h1>
                <p className="text-xl text-gray-700 max-w-2xl mx-auto">
                    An open-source evolution of scholarly publishing, reimagining OJS with a modern stack.
                </p>
            </header>

            <main className="bg-white shadow-lg rounded-lg p-8 max-w-3xl w-full">
                <p className="text-lg text-gray-600 mb-6">
                    MOJS is a fork of{' '}
                    <a href="https://pkp.sfu.ca/software/ojs/" className="text-primary hover:underline">Open Journal Systems (OJS)</a>,
                    originally developed by the{' '}
                    <a href="https://pkp.sfu.ca/" className="text-primary hover:underline">Public Knowledge Project (PKP)</a>.
                    Maintained by{' '}
                    <a href="https://website.anestesiudayana.com/" className="text-primary hover:underline">Balinesthesia</a>,
                    we're building with React, Rust, and Python for features like peer review matchmaking.
                </p>
                <p className="text-md text-gray-500 italic">
                    In early development as of April 2025—join us to shape its future!
                </p>
                <Counter />
            </main>

            <section className="mt-12 grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-3xl w-full">
                <a href="https://github.com/balinesthesia/modern-ojs"
                    className="bg-primary text-white text-center py-3 rounded-lg hover:bg-blue-600 transition-colors">
                    Source Code
                </a>
                <a href="https://balinesthesia.github.io/modern-ojs/docs/"
                    className="bg-secondary text-white text-center py-3 rounded-lg hover:bg-green-600 transition-colors">
                    Documentation
                </a>
                <a href="https://github.com/balinesthesia/modern-ojs/discussions"
                    className="bg-accent text-white text-center py-3 rounded-lg hover:bg-purple-600 transition-colors">
                    Discussions
                </a>
                <a href="https://github.com/balinesthesia/modern-ojs/issues"
                    className="bg-danger text-white text-center py-3 rounded-lg hover:bg-red-600 transition-colors">
                    Issues
                </a>
            </section>

            <footer className="mt-12 text-gray-500 text-sm text-center">
                <p>Stay tuned for our first release!</p>
            </footer>
        </div>
    );
};

export default App; 