import '@css/app.css';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import App from './app.tsx';
import { ContainerContext } from './container/container-context.tsx';
import { buildDiContainer } from './container/di-container.ts';

// * Bootstrap SM

const container = buildDiContainer()

const rootEl = document.getElementById('file-manager')

if (rootEl === null) {
    throw new Error('Root element not found');
}

createRoot(rootEl).render(
    <StrictMode>
        <ContainerContext.Provider value={container}>
            <App />
        </ContainerContext.Provider>
    </StrictMode>,
)
