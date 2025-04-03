import React, { useState } from 'react';

export const Counter: React.FC = () => {
    const [count, setCount] = useState(0);

    return (
        <div className="text-center">
            <p className="text-gray-600 mb-2">Clicked {count} times</p>
            <button
                className="bg-primary text-white py-2 px-4 rounded hover:bg-blue-600 transition-colors"
                onClick={() => setCount(count + 1)}
            >
                Click Me
            </button>
        </div>
    );
}; 