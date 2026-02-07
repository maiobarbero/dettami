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
            <p class="font-semibold mb-2">Last record:</p>
            <audio id="audioPlayback" controls class="w-full"></audio>
            <div id="uploadStatus" class="mt-2 text-center font-bold text-blue-600"></div>
        </div>
    </div>

    <script>
        let mediaRecorder;
        let audioChunks = [];

        const startBtn = document.getElementById('startBtn');
        const stopBtn = document.getElementById('stopBtn');
        const status = document.getElementById('status');
        const recIcon = document.getElementById('recIcon');
        const uploadStatus = document.getElementById('uploadStatus');
        const recordingsList = document.getElementById('recordingsList');
        const audioPlayback = document.getElementById('audioPlayback');

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
                    const audioUrl = URL.createObjectURL(audioBlob);
                    audioPlayback.src = audioUrl;
                    recordingsList.classList.remove('hidden');
                    
                    uploadAudio(audioBlob);
                    
                    // Stop tracks to release mic
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
            uploadStatus.innerText = "Uploading...";
            
            const formData = new FormData();
            // Usiamo estensione .webm che è standard per il browser recording
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
                
                if (response.ok) {
                    uploadStatus.innerText = "✅ Saved: " + result.path;
                    uploadStatus.className = "mt-2 text-center font-bold text-green-600";
                } else {
                    uploadStatus.innerText = "❌ Error: " + result.message;
                    uploadStatus.className = "mt-2 text-center font-bold text-red-600";
                }
            } catch (error) {
                console.error(error);
                uploadStatus.innerText = "❌ Network error";
            }
        }
    </script>
</body>
</html>
