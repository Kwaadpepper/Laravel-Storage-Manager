import '@css/app.css';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import App from './App.tsx';

const rootEl = document.getElementById('file-manager')

if (rootEl === null) {
    throw new Error('Root element not found');
}

createRoot(rootEl).render(
    <StrictMode>
        <App />
    </StrictMode>,
)
