<?php
// ---------------------------------------------------------------
// 1. SCAN CURRENT DIRECTORY FOR MP3 FILES
// ---------------------------------------------------------------
$mp3s = array_merge(
    glob('*.mp3') ?: [],
    glob('*.MP3') ?: []
);
$mp3s = array_values(array_unique($mp3s));
sort($mp3s, SORT_NATURAL | SORT_FLAG_CASE);

// ---------------------------------------------------------------
// 2. THE 18 EFFECTS (keys must match the JS object below)
// ---------------------------------------------------------------
$effects = [
    'distortion'      => 'Distortion',
    'bitcrusher'      => 'BitCrusher',
    'chebyshev'       => 'Chebyshev',
    'pitchshift'      => 'PitchShift',
    'frequencyshifter'=> 'FrequencyShifter',
    'autowah'         => 'AutoWah',
    'autofilter'      => 'AutoFilter',
    'phaser'          => 'Phaser',
    'chorus'          => 'Chorus',
    'tremolo'         => 'Tremolo',
    'vibrato'         => 'Vibrato',
    'autopanner'      => 'AutoPanner',
    'stereowidener'   => 'StereoWidener',
    'feedbackdelay'   => 'FeedbackDelay',
    'pingpongdelay'   => 'PingPongDelay',
    'freeverb'        => 'Freeverb',
    'jcreverb'        => 'JCReverb',
    'reverb'          => 'Reverb',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>MP3 Player + 18 Tone.js Effects</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tone/14.8.49/Tone.js"></script>
<style>
    body   { font-family: system-ui, sans-serif; background:#14161a; color:#e8e8e8;
             margin:0; padding:24px; }
    h1     { margin:0 0 20px; font-size:1.4rem; }
    h2     { font-size:1rem; color:#9aa4b2; margin:0 0 10px; text-transform:uppercase;
             letter-spacing:.08em; }
    section{ background:#1d2128; border:1px solid #2a2f38; border-radius:10px;
             padding:16px; margin-bottom:18px; }
    button { background:#2b6cb0; color:#fff; border:0; border-radius:6px;
             padding:8px 14px; cursor:pointer; font-size:.9rem; }
    button:hover { background:#3182ce; }
    .track { display:block; width:100%; text-align:left; background:#242a33;
             margin-bottom:6px; }
    .track:hover { background:#2f3742; }
    #now   { margin-left:12px; color:#9aa4b2; font-size:.9rem; }
    .fx-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(170px,1fr));
               gap:8px; }
    .fx    { display:flex; align-items:center; gap:8px; background:#242a33;
             padding:8px 10px; border-radius:6px; cursor:pointer; font-size:.85rem; }
    .fx:hover { background:#2f3742; }
    .empty { color:#e07b7b; }
</style>
</head>
<body>

<h1>🎵 MP3 Player + 18 Tone.js Effects</h1>

<!-- ============ PLAYLIST (generated from glob) ============ -->
<section>
    <h2>Playlist (<?= count($mp3s) ?> file<?= count($mp3s) === 1 ? '' : 's' ?>)</h2>
    <?php if (empty($mp3s)): ?>
        <p class="empty">No MP3 files found in this directory.</p>
    <?php else: ?>
        <?php foreach ($mp3s as $file): ?>
            <button class="track"
                    data-src="<?= htmlspecialchars(rawurlencode($file), ENT_QUOTES) ?>"
                    data-name="<?= htmlspecialchars($file, ENT_QUOTES) ?>">
                ▶ <?= htmlspecialchars($file) ?>
            </button>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<!-- ============ TRANSPORT ============ -->
<section>
    <h2>Transport</h2>
    <button id="stop">■ Stop</button>
    <span id="now">Nothing playing</span>
</section>

<!-- ============ 18 EFFECT CHECKBOXES ============ -->
<section>
    <h2>Effects Chain (toggle live)</h2>
    <div class="fx-grid">
        <?php foreach ($effects as $key => $label): ?>
            <label class="fx">
                <input type="checkbox" data-fx="<?= $key ?>">
                <?= htmlspecialchars($label) ?>
            </label>
        <?php endforeach; ?>
    </div>
</section>

<script>
(function () {
    // -----------------------------------------------------------
    // Chain order = signal flow order (top of list = first in chain)
    // -----------------------------------------------------------
    const ORDER = [
        'distortion','bitcrusher','chebyshev',
        'pitchshift','frequencyshifter',
        'autowah','autofilter','phaser','chorus','tremolo','vibrato','autopanner',
        'stereowidener',
        'feedbackdelay','pingpongdelay',
        'freeverb','jcreverb','reverb'
    ];

    // -----------------------------------------------------------
    // Instantiate all 18 effects once
    // -----------------------------------------------------------
    const fx = {
        distortion:       new Tone.Distortion({ distortion: 0.6, wet: 0.4 }),
        bitcrusher:       new Tone.BitCrusher({ bits: 6, wet: 0.4 }),
        chebyshev:        new Tone.Chebyshev({ order: 3, wet: 0.4 }),
        pitchshift:       new Tone.PitchShift({ pitch: 5, wet: 0.5 }),
        frequencyshifter: new Tone.FrequencyShifter({ frequency: 30, wet: 0.4 }),
        autowah:          new Tone.AutoWah({ baseFrequency: 100, octaves: 6, sensitivity: 0, wet: 0.5 }),
        autofilter:       new Tone.AutoFilter({ frequency: 1, depth: 0.7, wet: 0.5 }).start(),
        phaser:           new Tone.Phaser({ frequency: 0.5, octaves: 3, baseFrequency: 350 }),
        chorus:           new Tone.Chorus({ frequency: 1.5, delayTime: 3.5, depth: 0.7, wet: 0.5 }).start(),
        tremolo:          new Tone.Tremolo({ frequency: 5, depth: 0.5, wet: 0.5 }).start(),
        vibrato:          new Tone.Vibrato({ frequency: 5, depth: 0.1, wet: 0.5 }),
        autopanner:       new Tone.AutoPanner({ frequency: 1, depth: 0.8, wet: 0.5 }).start(),
        stereowidener:    new Tone.StereoWidener({ width: 0.7, wet: 0.5 }),
        feedbackdelay:    new Tone.FeedbackDelay({ delayTime: '8n', feedback: 0.3, wet: 0.4 }),
        pingpongdelay:    new Tone.PingPongDelay({ delayTime: '8n', feedback: 0.3, wet: 0.4 }),
        freeverb:         new Tone.Freeverb({ roomSize: 0.7, dampening: 3000, wet: 0.4 }),
        jcreverb:         new Tone.JCReverb({ roomSize: 0.5, wet: 0.4 }),
        reverb:           new Tone.Reverb({ decay: 2, wet: 0.4 })
    };

    // Headroom so 18 stacked effects don't clip
    Tone.Destination.volume.value = -6;

    let player = null;

    // -----------------------------------------------------------
    // Re-wire player -> checked effects -> speakers
    // -----------------------------------------------------------
    function rebuildChain() {
        if (!player) return;

        try { player.disconnect(); } catch (e) {}
        ORDER.forEach(id => { try { fx[id].disconnect(); } catch (e) {} });

        const active = ORDER
            .filter(id => document.querySelector(`input[data-fx="${id}"]`).checked)
            .map(id => fx[id]);

        if (active.length) {
            player.chain(...active, Tone.Destination);
        } else {
            player.toDestination();
        }
    }

    // -----------------------------------------------------------
    // Load + play a track
    // -----------------------------------------------------------
    async function playTrack(url, name) {
        await Tone.start();                       // unlock AudioContext

        if (player) {
            try { player.stop(); player.dispose(); } catch (e) {}
            player = null;
        }

        document.getElementById('now').textContent = '⏳ Loading ' + name + '…';

        player = new Tone.Player(url);
        await Promise.all([ Tone.loaded(), fx.reverb.ready ]);  // wait for decode + IR

        rebuildChain();
        player.start();
        document.getElementById('now').textContent = '▶ ' + name;
    }

    // -----------------------------------------------------------
    // UI wiring
    // -----------------------------------------------------------
    document.querySelectorAll('.track').forEach(btn => {
        btn.addEventListener('click', () => playTrack(btn.dataset.src, btn.dataset.name));
    });

    document.querySelectorAll('input[data-fx]').forEach(cb => {
        cb.addEventListener('change', rebuildChain);
    });

    document.getElementById('stop').addEventListener('click', () => {
        if (player) player.stop();
        document.getElementById('now').textContent = '■ Stopped';
    });
})();
</script>
</body>
</html>
