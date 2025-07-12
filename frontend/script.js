const recordButton = document.getElementById('recordButton');
const audioPlayer = document.getElementById('audioPlayer');
const transcriptContainer = document.getElementById('transcriptContainer');
const backendUrl = 'http://localhost:8000/backend/index.php'; // Placeholder

let isRecording = false;
let mediaRecorder;
let audioChunks = [];

recordButton.addEventListener('click', () => {
    if (isRecording) {
        stopRecording();
    } else {
        startRecording();
    }
});

function startRecording() {
    navigator.mediaDevices.getUserMedia({ audio: true })
        .then(stream => {
            mediaRecorder = new MediaRecorder(stream);
            mediaRecorder.start();
            isRecording = true;
            recordButton.textContent = 'Stop Recording';
            recordButton.classList.add('recording');
            audioChunks = [];

            mediaRecorder.addEventListener('dataavailable', event => {
                audioChunks.push(event.data);
            });

            mediaRecorder.addEventListener('stop', () => {
                const audioBlob = new Blob(audioChunks);
                sendAudioToBackend(audioBlob);
            });
        })
        .catch(error => {
            console.error('Error accessing microphone:', error);
        });
}

function stopRecording() {
    mediaRecorder.stop();
    isRecording = false;
    recordButton.textContent = 'Start Recording';
    recordButton.classList.remove('recording');
}

function sendAudioToBackend(audioBlob) {
    const formData = new FormData();
    formData.append('audio', audioBlob, 'recording.webm');

    fetch(backendUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        transcriptContainer.textContent = data.transcript;
        if (data.success) {
            const deepgramTtsUrl = 'https://api.deepgram.com/v1/speak?model=aura-asteria-en';

            fetch(deepgramTtsUrl, {
                method: 'POST',
                headers: {
                    'Authorization': `Token ${DEEPGRAM_API_KEY}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    text: data.message
                })
            })
            .then(response => response.blob())
            .then(blob => {
                const audioUrl = URL.createObjectURL(blob);
                audioPlayer.src = audioUrl;
                audioPlayer.play();
            })
            .catch(error => {
                console.error('Error with Deepgram TTS:', error);
            });
        }
    })
    .catch(error => {
        console.error('Error sending audio to backend:', error);
    });
}
