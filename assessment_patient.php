<?php
$session_code = $_GET['session_code'] ?? 'NULL';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CAST Assessment</title>
    <link rel="stylesheet" href="styles.css">
    <!-- **FIX**: Define the base URL for API calls -->
    <script>
        const BASE_URL = 'https://castslp.com';
    </script>
</head>
<body>
    <main class="dashboard-card patient-view">
        <div id="patient-instructions">
            <h1 id="scenario-title">Connecting to session...</h1>
            <p id="task-text" class="scenario-desc"></p>
        </div>
        <div id="image-container">
            <img id="scenario-image" src="" style="display:none;" class="scenario-image" alt="Scenario Image">
        </div>
    </main>

<script>
/* UI + polling */
document.addEventListener('DOMContentLoaded', () => {
    const sessionCode      = '<?= htmlspecialchars($_GET['session_code'] ?? 'NULL') ?>';
    const scenarioTitleEl  = document.getElementById('scenario-title');
    const taskTextEl       = document.getElementById('task-text');
    const scenarioImageEl  = document.getElementById('scenario-image');

    function updateUI(data) {
        if (data.status === 'in_progress' && data.current_task_idx >= 0) {
            scenarioTitleEl.textContent = `Scenario: ${data.current_scenario_text}`;
            taskTextEl.textContent      = data.current_task_text;

            if (data.current_image_url) {
                // **FIX**: Use the full URL for the image source.
                scenarioImageEl.src = `${BASE_URL}/${data.current_image_url}`;
                scenarioImageEl.style.display = 'block';
            } else {
                scenarioImageEl.style.display = 'none';
            }
        }
    }

    window.updateUI = updateUI;

    function getState() {
        // **FIX**: Use the full URL for the API call.
        fetch(`${BASE_URL}/api/get_state.php?session_code=${sessionCode}&role=patient`)
            .then(r => r.json())
            .then(data => {
                if (data.status === 'error') {
                    scenarioTitleEl.textContent = 'Error';
                    taskTextEl.textContent      = data.message;
                    clearInterval(pollInterval);
                    return;
                }
                if (data.status === 'finished') {
                    scenarioTitleEl.textContent = 'Assessment Complete';
                    taskTextEl.textContent      = 'Thank you. You can now close this window.';
                    clearInterval(pollInterval);
                } else if (data.status === 'ready_to_start') {
                    scenarioTitleEl.textContent = 'Please wait for the clinician to begin.';
                }


                window.updateUI(data);
                if (window.handleSessionState) window.handleSessionState(data);
            })
            .catch(console.error);
    }
    const pollInterval = setInterval(getState, 3000);
    getState();            
});
</script>

<script>
/* CAST Patient Recorder */
(() => {
    console.log('CAST recorder JS loaded');

    let recorderStarted = false;
    let mediaRecorder   = null;
    let recordedChunks  = [];
    let mixedStream     = null;
    let lastStatus      = null;
    let firstGesture    = false;

    const sessionCode = '<?= htmlspecialchars($session_code) ?>';
    const wantsRecording = s => !!(s.record_session ?? s.record);

    document.body.addEventListener('click', () => {
        firstGesture = true;                       // User interaction unlocks getDisplayMedia
        console.log('firstGesture = true (user clicked)');
    }, { once:true });


    async function startRecording () {
    if (recorderStarted) return;
    recorderStarted = true;
    console.log('➡️  startRecording() with PiP');

    try {
    
        const screen = await navigator.mediaDevices.getDisplayMedia({
            video: { frameRate: 30 },
            audio: false
        });

        const cam = await navigator.mediaDevices.getUserMedia({
            video: { width: 640, height: 360, frameRate: 30 },
            audio: { echoCancellation: true, noiseSuppression: true }
        });
        const [micTrack] = cam.getAudioTracks();

        const scrVid = Object.assign(document.createElement('video'), { srcObject: screen, muted: true });
        const camVid = Object.assign(document.createElement('video'), { srcObject: cam, muted: true });
        await Promise.all([scrVid.play(), camVid.play()]);

        const { width, height } = screen.getVideoTracks()[0].getSettings();
        const canvas = Object.assign(document.createElement('canvas'), { width, height });
        const ctx     = canvas.getContext('2d');

        (function draw () {
            ctx.drawImage(scrVid, 0, 0, width, height);             
            const pipW = Math.round(width * 0.25);                  
            const pipH = Math.round(pipW * (camVid.videoHeight / camVid.videoWidth || 0.75));
            ctx.drawImage(camVid, 0, 0, pipW, pipH);                
            requestAnimationFrame(draw);
        })();

        const canvasStream = canvas.captureStream(30);               
        mixedStream        = new MediaStream([
            canvasStream.getVideoTracks()[0],
            micTrack
        ]);

        mediaRecorder = new MediaRecorder(mixedStream, {
            mimeType: 'video/webm;codecs=vp9,opus'
        });
        mediaRecorder.ondataavailable = e => recordedChunks.push(e.data);
        mediaRecorder.start();
        console.log('recording started (canvas + mic)');
    } catch (err) {
        console.error('startRecording failed', err);
        recorderStarted = false;
    }
}

    async function stopRecordingAndUpload() {
        if (!recorderStarted) return;

        console.log('➡️  stopRecordingAndUpload() called');
        return new Promise(res => {
            mediaRecorder.onstop = async () => {
                try {
                    const blob = new Blob(recordedChunks, {type: 'video/webm'});
                    const form = new FormData();
                    form.append('session_code', sessionCode);
                    form.append('video', blob, `${Date.now()}.webm`);
                    // **FIX**: Use the full URL for the API call.
                    await fetch(`${BASE_URL}/api/upload_recording.php`, {method: 'POST', body: form});
                    console.log('📤 upload finished');
                } catch (err) {
                    console.error('upload failed', err);
                }
                res();
            };

            if (mediaRecorder.state !== 'inactive') {
                mediaRecorder.stop();
            }
            mixedStream.getTracks().forEach(t => t.stop());
            recorderStarted = false;
        });
    }

    async function handleSessionState(state) {
        console.log(
            '[handleSessionState] status=%s wantsRec=%s firstGesture=%s started=%s',
            state.status, wantsRecording(state), firstGesture, recorderStarted
        );

        if (!firstGesture) return;                            // wait for user gesture

        if (wantsRecording(state) &&
            state.status === 'in_progress' &&
            !recorderStarted) {
            await startRecording();
        }
        if (recorderStarted &&
            state.status === 'finished' &&
            lastStatus !== 'finished') {
            await stopRecordingAndUpload();
        }
        lastStatus = state.status;
    }

    window.handleSessionState = handleSessionState;
    window.addEventListener('beforeunload', stopRecordingAndUpload);
})();
</script>
</body>
</html>
