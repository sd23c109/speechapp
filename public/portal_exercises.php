<?php
$GLOBALS['current_dashboard'] = 'patientportal';
include('../../dashboards/_init.php');
include('_menu_loader.php');


?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Exercises (POC)</title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    
    <meta content="MKAdvantage" name="author" />

    <!-- Favicon -->
    <link rel="shortcut icon" href="img/favicon.ico">

    <!-- Toastr css -->
    <link href="plugins/toastr/css/toastr.min.css" rel="stylesheet">

    <!-- Gritter -->
    <link href="plugins/gritter/css/jquery.gritter.css" rel="stylesheet">

    <!-- Bootstrap css -->
    <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css">

    <!-- Icons css -->
    <link href="plugins/fontawesome/css/all.min.css" rel="stylesheet" type="text/css">

    <!-- Animate.css -->
    <link href="plugins/animate/css/animate.min.css" rel="stylesheet">

    <!-- Formbuilder -->
    <script src="plugins/jquery/js/jquery.min.js"></script>
    <link rel="stylesheet" href="/mka-assets/formbuilder/form-builder.min.css">
    <script src="/mka-assets/formbuilder/form-builder.min.js"></script>
    <script src="/mka-assets/formbuilder/form-render.min.js"></script>


    <!-- Style css -->
    <link href="css/style.min.css" rel="stylesheet" type="text/css">

    <!-- Head.js -->
    <script src="js/head.js"></script>
    <style>
        .category-btn { padding: 14px 18px; font-weight: 600; }
        .slide-panel {
            overflow: hidden;
            max-height: 0;
            transition: max-height .35s ease;
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
            margin-top: .5rem;
            padding: 0 12px;
        }
        .slide-panel.open { max-height: 400px; padding: 12px; }
        .exercise-card {
            border: 1px solid #e5e7eb;
            border-radius: .75rem;
            padding: 24px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 160px;
            font-size: 64px;
            font-weight: 800;
            letter-spacing: .05em;
        }
        .pill {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 10px 16px; border-radius: 999px; border: 1px solid #e5e7eb; background: #fff;
            cursor: pointer; margin: 6px; font-weight: 600;
        }
        .pill:hover { background: #f1f5f9; }
        .controls { display:flex; align-items:center; gap:12px; }
        .controls .nav-btn {
            border:1px solid #d1d5db; background:#fff; border-radius: .5rem; padding:10px 14px;
            display:inline-flex; align-items:center; gap:6px; cursor:pointer;
        }
        .controls .nav-btn:hover { background:#f8fafc; }
        .success-btn {
            background:#22c55e; color:#fff; border:none; padding:12px 18px; border-radius:.5rem; font-weight:700;
        }
        .success-btn:hover { filter: brightness(0.95); }
        .ghost { opacity:.4; pointer-events:none; }
    </style>
</head>
<body class="container py-4">

<h2 class="mb-3">Exercises</h2>

<!-- Top category buttons -->
<div class="d-flex gap-2">
    <button class="btn btn-primary category-btn" data-panel="panel-consonants">Consonants</button>
    <button class="btn btn-primary category-btn" data-panel="panel-vowels">Vowels</button>
    <button class="btn btn-primary category-btn" data-panel="panel-cv">CV Blending</button>
</div>

<!-- Slide-down selection panels -->
<div id="panel-consonants" class="slide-panel mt-2">
    <div class="d-flex flex-wrap py-2" id="consonant-list"></div>
</div>
<div id="panel-vowels" class="slide-panel mt-2">
    <div class="d-flex flex-wrap py-2" id="vowel-list"></div>
</div>
<div id="panel-cv" class="slide-panel mt-2">
    <div class="d-flex flex-wrap py-2" id="cv-list"></div>
</div>

<!-- Main exercise area -->
<div class="row mt-4 g-4">
    <div class="col-md-8">
        <div class="exercise-card" id="exercise-view">
            <!-- Exercise rendering goes here -->
            <span class="text-muted">Pick an exercise above</span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 border rounded h-100">
            <h6 class="mb-3">Help</h6>
            <button class="btn btn-outline-primary w-100" id="btnVideo">Video Example</button>
            <div class="mt-3 small text-muted">Watch a quick demo of how to perform this exercise.</div>
        </div>
    </div>
</div>

<!-- Dynamic choices below -->
<div class="mt-4">
    <h6 class="mb-2" id="choices-title" style="display:none;"></h6>
    <div id="choices-wrap" class="d-flex flex-wrap"></div>
</div>

<!-- Controls -->
<div class="mt-4 d-flex justify-content-between align-items-center">
    <div class="controls">
        <button class="nav-btn" id="btnBack" title="Previous">
            &#x25C0; <span>Back</span>
        </button>
        <button class="nav-btn" id="btnNext" title="Next">
            <span>Next</span> &#x25B6;
        </button>
    </div>
    <button class="success-btn" id="btnSuccess">Success</button>
</div>

<!-- Video Modal -->
<div class="modal fade" id="videoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoTitle">Video Example</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Replace with real video/iframe -->
                <div class="ratio ratio-16x9 border rounded">
                    <video id="videoPlayer" controls>
                        <source src="" type="video/mp4">
                    </video>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content text-center p-4">
            <h4 class="mb-2">Nice job!</h4>
            <img src="/img/success.png" onerror="this.style.display='none';" class="img-fluid mb-2" alt="Success">
            <p class="text-muted mb-0">You completed this exercise.</p>
        </div>
    </div>
</div>

<script src="/assets/js/bootstrap.bundle.min.js"></script>
<script>
    // ---------- Mock data (replace with DB later) ----------
    const CONSONANTS = ["S","T","L","N","P","D","R","F","J","M","K","B"];
    const VOWELS = [
        {code:"AH", label:"AH"}, {code:"EE", label:"EE"}, {code:"OO", label:"OO"},
        {code:"AY", label:"AY"}, {code:"IH", label:"IH"}
    ];

    // Linear sequence for prev/next across "selected" items
    // For the POC: sequence is all vowels then all consonants, then a few CVs
    const SEQUENCE = [
        ...VOWELS.map(v => ({type:"vowel", id:v.code})),
        ...CONSONANTS.map(c => ({type:"consonant", id:c})),
        // a few example CVs
        {type:"cv", id:"S-AH"}, {type:"cv", id:"T-EE"}, {type:"cv", id:"L-OO"}
    ];

    // Mock video per item (optional)
    function videoFor(item){
        // Return a placeholder mp4 path or empty string
        return ""; // e.g. `/media/${item.type}-${item.id}.mp4`
    }

    // ---------- State ----------
    let currentIndex = -1;

    // ---------- UI wiring ----------
    // Build selection lists
    function buildLists() {
        const $cl = document.getElementById('consonant-list');
        const $vl = document.getElementById('vowel-list');
        const $cv = document.getElementById('cv-list');

        CONSONANTS.forEach(c => {
            const b = buttonPill(c, () => selectByObject({type:"consonant", id:c}));
            $cl.appendChild(b);
        });

        VOWELS.forEach(v => {
            const b = buttonPill(v.label, () => selectByObject({type:"vowel", id:v.code}));
            $vl.appendChild(b);
        });

        // simple CV grid
        VOWELS.slice(0,4).forEach(v=>{
            CONSONANTS.slice(0,6).forEach(c=>{
                const label = `${c}-${v.code}`;
                $cv.appendChild(buttonPill(label, () => selectByObject({type:"cv", id:label})));
            })
        });
    }

    function buttonPill(text, onClick) {
        const el = document.createElement('div');
        el.className = 'pill';
        el.textContent = text;
        el.addEventListener('click', onClick);
        return el;
    }

    // Slide panels
    document.querySelectorAll('.category-btn').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            const target = btn.dataset.panel;
            document.querySelectorAll('.slide-panel').forEach(p=>{
                if (p.id === target) p.classList.toggle('open');
                else p.classList.remove('open');
            });
        });
    });

    // Selection
    function selectByObject(obj){
        // find or push into sequence if ad-hoc
        let idx = SEQUENCE.findIndex(x => x.type===obj.type && x.id===obj.id);
        if (idx === -1) { SEQUENCE.push(obj); idx = SEQUENCE.length-1; }
        currentIndex = idx;
        renderExercise(SEQUENCE[currentIndex]);

        // close all panels on select
        document.querySelectorAll('.slide-panel').forEach(p=>p.classList.remove('open'));
    }

    // Render current exercise
    function renderExercise(item){
        const view = document.getElementById('exercise-view');
        view.innerHTML = "";
        const span = document.createElement('span');
        span.textContent = prettyLabel(item);
        view.appendChild(span);

        // set video
        const vsrc = videoFor(item);
        document.getElementById('videoPlayer').src = vsrc;

        // show dynamic choices
        const title = document.getElementById('choices-title');
        const wrap = document.getElementById('choices-wrap');
        wrap.innerHTML = "";

        if (item.type === "vowel") {
            title.style.display = "";
            title.textContent = "Pick a consonant to blend with this vowel:";
            CONSONANTS.forEach(c=>{
                wrap.appendChild(buttonPill(c, ()=>selectByObject({type:"cv", id:`${c}-${item.id}`})));
            });
        } else if (item.type === "consonant") {
            title.style.display = "";
            title.textContent = "Pick a vowel to blend with this consonant:";
            VOWELS.forEach(v=>{
                wrap.appendChild(buttonPill(v.label, ()=>selectByObject({type:"cv", id:`${item.id}-${v.code}`})));
            });
        } else {
            title.style.display = "none";
        }

        // nav button states
        document.getElementById('btnBack').classList.toggle('ghost', currentIndex<=0);
        document.getElementById('btnNext').classList.toggle('ghost', currentIndex>=SEQUENCE.length-1);
    }

    function prettyLabel(item){
        if (item.type==='cv') return item.id.replace('-', ' + ');
        if (item.type==='vowel') return item.id;
        if (item.type==='consonant') return item.id;
        return item.id;
    }

    // Nav
    document.getElementById('btnBack').addEventListener('click', ()=>{
        if (currentIndex>0){ currentIndex--; renderExercise(SEQUENCE[currentIndex]); }
    });
    document.getElementById('btnNext').addEventListener('click', ()=>{
        if (currentIndex<SEQUENCE.length-1){ currentIndex++; renderExercise(SEQUENCE[currentIndex]); }
    });

    // Video
    document.getElementById('btnVideo').addEventListener('click', ()=>{
        const modal = new bootstrap.Modal(document.getElementById('videoModal'));
        modal.show();
    });

    // Success: show modal, advance on close
    const successModalEl = document.getElementById('successModal');
    successModalEl.addEventListener('hidden.bs.modal', ()=>{
        // advance to next
        if (currentIndex<SEQUENCE.length-1){ currentIndex++; renderExercise(SEQUENCE[currentIndex]); }
    });
    document.getElementById('btnSuccess').addEventListener('click', ()=>{
        const modal = new bootstrap.Modal(successModalEl);
        modal.show();
    });

    // Init
    buildLists();
    // Optional: start on first vowel
    selectByObject(SEQUENCE[0]);
</script>
</body>
</html>

