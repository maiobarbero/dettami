<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dettami - Voice Transcription Assistant</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-slate-50 text-slate-900 font-sans min-h-screen flex flex-col items-center justify-center selection:bg-blue-500 selection:text-white">

    <div class="w-full max-w-lg px-6 py-12 bg-white rounded-2xl shadow-xl sm:px-10 border border-slate-100">
        <div class="text-center mb-10">
            <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 mb-2">Dettami</h1>
            <p class="text-slate-500 text-lg">Your virtual assistant for instant voice notes.</p>
        </div>

        <div class="flex flex-col items-center justify-center space-y-8">
            <!-- Status Indicator -->
            <div id="status-container" class="flex items-center space-x-2 px-4 py-2 bg-slate-100 rounded-full transition-colors duration-300">
                <div id="status-dot" class="w-2.5 h-2.5 bg-slate-400 rounded-full"></div>
                <span id="status-text" class="text-sm font-medium text-slate-600">Ready to record</span>
            </div>

            <!-- Main Controls -->
            <div class="flex items-center gap-6">
                <button id="startBtn" class="group relative flex items-center justify-center w-20 h-20 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-full shadow-lg hover:shadow-blue-500/30 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-blue-500/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                    <span class="absolute -bottom-8 text-xs font-semibold text-slate-500 opacity-0 group-hover:opacity-100 transition-opacity">Record</span>
                </button>

                <button id="stopBtn" disabled class="group relative flex items-center justify-center w-20 h-20 bg-slate-200 text-slate-400 rounded-full transition-all duration-300 cursor-not-allowed disabled:opacity-70 data-[active=true]:bg-red-500 data-[active=true]:text-white data-[active=true]:shadow-lg data-[active=true]:hover:shadow-red-500/30 data-[active=true]:cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
                    </svg>
                    <span class="absolute -bottom-8 text-xs font-semibold text-slate-500 opacity-0 group-hover:opacity-100 transition-opacity">Stop</span>
                </button>
            </div>
        </div>

        <!-- Transcription Result -->
        <div id="result-area" class="mt-10 hidden opacity-0 transition-opacity duration-500">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wider">Transcription</h3>
                <button id="copyBtn" class="text-xs font-medium text-blue-600 hover:text-blue-700 hover:underline focus:outline-none">
                    Copy text
                </button>
            </div>
            <div class="relative">
                <textarea id="transcriptionText" class="w-full h-40 p-4 text-base text-slate-800 bg-slate-50 border border-slate-200 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition-shadow" readonly></textarea>
                <div id="loading-overlay" class="absolute inset-0 bg-white/50 backdrop-blur-[1px] rounded-xl flex items-center justify-center hidden">
                    <div class="w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                </div>
            </div>
        </div>
    </div>

    <footer class="mt-12 text-slate-400 text-sm">
        &copy; {{ date('Y') }} <a href="https://www.maiobarbero.dev/">Matteo Barbero</a>. All rights reserved.
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let mediaRecorder;
            let audioChunks = [];

            const startBtn = document.getElementById('startBtn');
            const stopBtn = document.getElementById('stopBtn');
            const statusText = document.getElementById('status-text');
            const statusDot = document.getElementById('status-dot');
            const resultArea = document.getElementById('result-area');
            const transcriptionText = document.getElementById('transcriptionText');
            const copyBtn = document.getElementById('copyBtn');
            const loadingOverlay = document.getElementById('loading-overlay');

            const setStatus = (state) => {
                // States: 'idle', 'recording', 'processing', 'done', 'error'
                switch(state) {
                    case 'idle':
                        statusText.textContent = "Ready to record";
                        statusDot.className = "w-2.5 h-2.5 bg-slate-400 rounded-full";
                        startBtn.disabled = false;
                        startBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                        stopBtn.disabled = true;
                        stopBtn.removeAttribute('data-active');
                        break;
                    case 'recording':
                        statusText.textContent = "Recording...";
                        statusDot.className = "w-2.5 h-2.5 bg-red-500 rounded-full animate-pulse";
                        startBtn.disabled = true;
                        startBtn.classList.add('opacity-50', 'cursor-not-allowed');
                        stopBtn.disabled = false;
                        stopBtn.setAttribute('data-active', 'true');
                        break;
                    case 'processing':
                        statusText.textContent = "Processing audio...";
                        statusDot.className = "w-2.5 h-2.5 bg-blue-500 rounded-full animate-bounce";
                        startBtn.disabled = true;
                        stopBtn.disabled = true;
                        stopBtn.removeAttribute('data-active');
                        loadingOverlay.classList.remove('hidden');
                        break;
                    case 'done':
                        statusText.textContent = "Transcription complete";
                        statusDot.className = "w-2.5 h-2.5 bg-green-500 rounded-full";
                        startBtn.disabled = false;
                        startBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                        loadingOverlay.classList.add('hidden');
                        break;
                    case 'error':
                        statusText.textContent = "Error occurred";
                        statusDot.className = "w-2.5 h-2.5 bg-red-600 rounded-full";
                        startBtn.disabled = false;
                        startBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                        loadingOverlay.classList.add('hidden');
                        break;
                }
            };

            startBtn.addEventListener('click', async () => {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    mediaRecorder = new MediaRecorder(stream);
                    audioChunks = [];

                    mediaRecorder.ondataavailable = event => {
                        audioChunks.push(event.data);
                    };

                    mediaRecorder.onstop = async () => {
                        const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                        
                        // Stop all tracks to release microphone
                        stream.getTracks().forEach(track => track.stop());

                        setStatus('processing');
                        
                        // Show result area if hidden
                        resultArea.classList.remove('hidden');
                        setTimeout(() => resultArea.classList.remove('opacity-0'), 10); // Fade in
                        transcriptionText.value = ""; // Clear previous

                        await uploadAudio(audioBlob);
                    };

                    mediaRecorder.start();
                    setStatus('recording');

                } catch (err) {
                    console.error(err);
                    alert("Microphone access denied or error: " + err.message);
                    setStatus('idle');
                }
            });

            stopBtn.addEventListener('click', () => {
                if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                    mediaRecorder.stop();
                }
            });

            async function uploadAudio(blob) {
                const formData = new FormData();
                formData.append('audio', blob, 'recording.webm');

                try {
                    const response = await fetch('/recorder/upload', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    const result = await response.json();

                    if (response.ok && result.transcription) {
                        transcriptionText.value = result.transcription;
                        setStatus('done');
                    } else {
                        transcriptionText.value = "Error: " + (result.message || "Unknown error");
                        setStatus('error');
                    }
                } catch (error) {
                    console.error(error);
                    transcriptionText.value = "Network Error: " + error.message;
                    setStatus('error');
                }
            }

            copyBtn.addEventListener('click', () => {
                if (!transcriptionText.value) return;
                
                transcriptionText.select();
                navigator.clipboard.writeText(transcriptionText.value).then(() => {
                    const originalText = copyBtn.innerText;
                    copyBtn.innerText = "Copied!";
                    setTimeout(() => copyBtn.innerText = originalText, 2000);
                }).catch(err => {
                    console.error('Failed to copy: ', err);
                });
            });
        });
    </script>
</body>
</html>
