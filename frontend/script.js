const recordButton = document.getElementById('recordButton');
const audioPlayer = document.getElementById('audioPlayer');
const conversationContainer = document.getElementById('conversationContainer');
const propertiesContainer = document.getElementById('propertiesContainer');
const backendUrl = 'http://localhost:8000/backend/index.php';

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
            recordButton.textContent = 'Stop Talking';
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
    recordButton.textContent = 'Start Talking';
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
        if (data.transcript) {
            const userMessage = document.createElement('p');
            userMessage.innerHTML = `<strong>You:</strong> ${data.transcript}`;
            conversationContainer.appendChild(userMessage);
        }

        if (data.message) {
            playAndDisplayAgentMessage(data.message);
        }

        if (data.properties) {
            displayProperties(data.properties);
        }
    })
    .catch(error => {
        console.error('Error sending audio to backend:', error);
    });
}

function playAndDisplayAgentMessage(message) {
    const agentMessage = document.createElement('p');
    agentMessage.innerHTML = `<strong>Agent:</strong> ${message}`;
    conversationContainer.appendChild(agentMessage);

    const deepgramTtsUrl = `https://api.deepgram.com/v1/speak?model=aura-asteria-en`;
    fetch(deepgramTtsUrl, {
        method: 'POST',
        headers: {
            'Authorization': `Token ${DEEPGRAM_API_KEY}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ text: message })
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

function displayProperties(properties) {
    propertiesContainer.innerHTML = '';
    properties.slice(0, 6).forEach(property => {
        const propertyDiv = document.createElement('div');
        propertyDiv.className = 'property';
        propertyDiv.innerHTML = `
            <img src="${property.image}" alt="${property.title}">
            <h3>${property.title}</h3>
            <p>${property.price}</p>
            <p>${property.location}</p>
        `;
        propertiesContainer.appendChild(propertyDiv);
    });
}

// Initial message
playAndDisplayAgentMessage("Hello! I'm your real estate assistant. Are you looking to buy or rent?");
