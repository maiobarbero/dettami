# Dettami

Dettami ("Dictate to me") is a minimal, AI-powered virtual assistant designed for instant voice-to-text transcription.

![Dettami homepage screenshot](https://raw.githubusercontent.com/maiobarbero/dettami/refs/heads/main/screenshots/homepage.png)


## Features

- **Instant Recording:** Capture voice notes directly from your browser.
- **AI Transcription:** Uses advanced speech-to-text technology to transcribe your audio files.
- **Minimal UI:** A clean, distraction-free interface focused on speed and simplicity.
- **One-Click Copy:** Easily copy transcribed text to your clipboard.

## Getting Started

### Prerequisites

- PHP ^8.2+
- Composer
- Node.js & NPM

### Installation

1.  Clone the repository.
2.  Install PHP dependencies:
    ```bash
    composer install
    ```
3.  Install Frontend dependencies:
    ```bash
    npm install
    npm run build
    ```
4.  Configure your environment:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    *Ensure you configure your database and AI service credentials in `.env`.*

5.  Run migrations:
    ```bash
    php artisan migrate
    ```

6.  Serve the application:
    ```bash
    php artisan serve
    ```

## Usage

Visit the application in your browser (usually `http://localhost:8000`). Click the microphone button to start recording, and stop to process the audio.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
