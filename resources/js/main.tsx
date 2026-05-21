import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import App from '~/app-router';
import '../css/app.css';

// Force dark mode
document.documentElement.classList.add('dark');
document.documentElement.style.colorScheme = 'dark';
localStorage.setItem('appearance', 'dark');

const root = document.getElementById('root');

if (!root) {
  throw new Error('Root element not found');
}

createRoot(root).render(
  <StrictMode>
    <App />
  </StrictMode>,
);
