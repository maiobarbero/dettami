<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dettami - Audio transcriptions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="bg-gray-100 flex items-center justify-center h-screen">

    <div class="bg-white p-8 rounded-lg shadow-lg text-center max-w-md w-full">
        <h1 class="text-2xl font-bold mb-6 text-gray-800">Dettami</h1>

        <div id="status" class="mb-4 text-gray-600">Ready to record</div>

        <div class="flex justify-center gap-4 mb-6">
            <button id="startBtn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-full transition shadow-md flex items-center gap-2">
                <span class="w-3 h-3 bg-red-500 rounded-full animate-pulse hidden" id="recIcon"></span>
                Start
            </button>
            <button id="stopBtn" disabled class="bg-gray-400 cursor-not-allowed text-white font-bold py-3 px-6 rounded-full transition shadow-md">
                Stop & Save
            </button>
        </div>

        <div id="recordingsList" class="text-left text-sm text-gray-500 mt-4 border-t pt-4 hidden">
            <div id="transcriptionBox" class="mt-4 hidden">
                <p class="font-semibold mb-2">Transcription:</p>
                <div class="relative">
                    <textarea id="transcriptionText" class="w-full p-3 border rounded h-32 text-gray-700 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500" readonly></textarea>
                    <button id="copyBtn" class="absolute top-2 right-2 bg-white hover:bg-gray-100 text-gray-700 border shadow-sm px-2 py-1 rounded text-xs font-bold transition">
                        Copy
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let mediaRecorder;
        let audioChunks = [];

        const startBtn = document.getElementById('startBtn');
        const stopBtn = document.getElementById('stopBtn');
        const status = document.getElementById('status');
        const recIcon = document.getElementById('recIcon');
        const recordingsList = document.getElementById('recordingsList');
        const transcriptionBox = document.getElementById('transcriptionBox');
        const transcriptionText = document.getElementById('transcriptionText');
        const copyBtn = document.getElementById('copyBtn');

        startBtn.addEventListener('click', async () => {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    audio: true
                });

                mediaRecorder = new MediaRecorder(stream);

                audioChunks = [];

                mediaRecorder.ondataavailable = event => {
                    audioChunks.push(event.data);
                };

                mediaRecorder.onstop = async () => {
                    const audioBlob = new Blob(audioChunks, {
                        type: 'audio/webm'
                    });
                    const audioUrl = URL.createObjectURL(audioBlob);
                    recordingsList.classList.remove('hidden');
                    transcriptionBox.classList.add('hidden');

                    uploadAudio(audioBlob);

                    stream.getTracks().forEach(track => track.stop());
                };

                mediaRecorder.start();

                status.innerText = "Recording...";
                status.classList.add('text-red-600', 'font-bold');
                startBtn.disabled = true;
                startBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
                startBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');

                stopBtn.disabled = false;
                stopBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
                stopBtn.classList.add('bg-red-600', 'hover:bg-red-700');

                recIcon.classList.remove('hidden');

            } catch (err) {
                alert("Errore accesso microfono: " + err);
            }
        });

        stopBtn.addEventListener('click', () => {
            mediaRecorder.stop();

            status.innerText = "Saving...";
            status.classList.remove('text-red-600', 'font-bold');

            startBtn.disabled = false;
            startBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
            startBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');

            stopBtn.disabled = true;
            stopBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
            stopBtn.classList.remove('bg-red-600', 'hover:bg-red-700');

            recIcon.classList.add('hidden');
        });

        async function uploadAudio(blob) {
            const formData = new FormData();
            formData.append('audio', blob, 'recording.webm');

            transcriptionBox.classList.remove('hidden');
            transcriptionText.value = "Processing...";

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
                } else {
                    transcriptionText.value = "Error: " + (result.message || "Unknown error");
                }
            } catch (error) {
                console.error(error);
                transcriptionText.value = "Error: " + error.message;
            }
        }

        copyBtn.addEventListener('click', () => {
            transcriptionText.select();
            navigator.clipboard.writeText(transcriptionText.value).then(() => {
                const originalText = copyBtn.innerText;
                copyBtn.innerText = "Copied!";
                setTimeout(() => copyBtn.innerText = originalText, 2000);
            });
        });
    </script>
</body>

</html>
