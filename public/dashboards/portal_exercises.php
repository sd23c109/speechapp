<?php
$GLOBALS['current_dashboard'] = 'patientportal';
include('../../dashboards/_init.php');
include('_menu_loader.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MKAdvantage – Exercises (POC)</title>

    <link rel="shortcut icon" href="img/favicon.ico">
    <link href="plugins/toastr/css/toastr.min.css" rel="stylesheet">
    <link href="plugins/gritter/css/jquery.gritter.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="plugins/fontawesome/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="plugins/animate/css/animate.min.css" rel="stylesheet">
    <link href="css/style.min.css" rel="stylesheet" type="text/css">

    <script src="plugins/jquery/js/jquery.min.js"></script>

    <style>
        /* --- Exercise UI (minimal, theme-friendly) --- */
        .category-btn { padding: 10px 14px; font-weight: 600; }
        .slide-panel {
            max-height: 70vh;
            overflow-y: auto;
            overflow-x: hidden;
            padding-bottom: 12px;
            transition: max-height .3s ease;
            border: none;
            background: transparent;
            padding: 0 12px;
            margin-top: .5rem;
            border-radius: .5rem;
            display: none;
        }

        /* Expanded state — restore your border + background */
        .slide-panel.open {
            max-height: 360px;
            padding: 12px;
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            display: block;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            background: #fff;
            cursor: pointer;
            margin: 6px;
            font-weight: 600;
        }
        .pill:hover { background: #f1f5f9; }

        .controls .nav-btn {
            border:1px solid #d1d5db;
            background:#fff;
            border-radius:.5rem;
            padding:10px 14px;
            display:inline-flex;
            align-items:center;
            gap:6px;
            cursor:pointer;
        }
        .controls .nav-btn:hover { background:#f8fafc; }

        /* Success button */
        .success-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight:700;
        }
        .success-btn:disabled {
            background-color: #c6c6c6 !important;
            color: #7a7a7a !important;
            cursor: not-allowed;
            opacity: 1 !important;
        }
        .success-btn:not(:disabled):hover {
            background-color: #218838;
        }

        .ghost { opacity:.4; pointer-events:none; }

        /* ===========================
           CARD GRID + CARD LAYOUT
           =========================== */

        /* Grid container for multiple exercise cards */
        #exercise-view {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
            align-items: flex-start;
        }

        /* One flex item per card (content box + button) */
        .exercise-card {
            flex: 0 0 calc(16.66% - 12px);   /* 6 across desktop */
            max-width: calc(16.66% - 12px);
            display: flex;
            flex-direction: column;          /* box on top, button under */
            align-items: stretch;
        }

        /* Inner bordered box */
        .exercise-box {
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 10px;
            min-height: 200px;
            background-color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Area that holds the images/text */
        .exercise-content {
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* When a card is completed, hide its inner content but keep the box */
        .exercise-card.completed .exercise-content {
            visibility: hidden;
        }

        /* Change Card button under each box */
        .change-card-btn {
            margin-top: 6px;
            width: 100%;
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            background-color: #28a745;
            color: #fff;
            cursor: pointer;
            font-size: 0.85rem;
        }
        .change-card-btn:hover {
            background-color: #218838;
        }

        /* Top image */
        .exercise-main-img {
            display: block;
            margin: 0 auto;
            max-width: 100%;
        }

        /* Icon underneath */
        .exercise-icon-img {
            display: block;
            margin: 8px auto 0;
            max-width: 100%;
        }

        .exercise-card.selected {
            outline: 2px solid #007bff;
        }

        .cv-bottom-row {
            margin-top: 8px;
            display: flex;
            justify-content: center;
            gap: 8px;
        }

        .cv-bottom-row img {
            max-width: 100px;
            height: auto;
        }

        .word-bottom-row {
            margin-top: 8px;
            display: flex;
            justify-content: center;
            align-items: baseline;
            gap: 4px;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .word-part {
            text-transform: lowercase;
        }

        .word-plus {
            opacity: 0.7;
        }

        /* Responsive: 3 per row on tablets */
        @media (max-width: 991.98px) {
            .exercise-card {
                flex: 0 0 calc(33.33% - 12px);
                max-width: calc(33.33% - 12px);
            }
        }

        /* Responsive: 2 per row on phones */
        @media (max-width: 575.98px) {
            .exercise-card {
                flex: 0 0 calc(50% - 12px);
                max-width: calc(50% - 12px);
            }
        }

        /* Force every card’s bordered box to be the same height */
        .exercise-box{
            height: 220px;              /* tweak this number */
            min-height: 220px;          /* keep consistent even if overridden elsewhere */
            overflow: hidden;
        }

        /* Keep content centered */
        .exercise-content{
            height: 100%;
        }

        /* Constrain images so they can't make the box taller */
        .exercise-main-img{
            max-height: 140px;          /* tweak */
            width: 100%;
            object-fit: contain;
        }

        .exercise-icon-img{
            max-height: 60px;           /* tweak */
            width: 100%;
            object-fit: contain;
        }

        /* Constrain the bottom row images too (CV mix area) */
        .cv-bottom-row img{
            max-height: 60px;           /* tweak */
            width: auto;
            object-fit: contain;
        }

        .cv-parts-text{
            font-size: 34px;
            font-weight: 900;
            padding: 6px 10px;
            border-radius: 10px;
            display: inline-block;
        }

        .cv-whole-text{
            font-size: 34px;
            font-weight: 900;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }

        .pill-section-header{
            font-weight: 800;
            margin: 12px 0 6px;
            opacity: .85;
        }

        .slide-panel{
            max-height: 70vh;
            overflow-y: auto;
            overflow-x: hidden;
            padding-bottom: 12px;
        }


    </style>

</head>

<body>
<div id="wrapper">

    <?=$menu?>

    <div id="page-wrapper" class="gray-bg dashbard-1">

        <?=$topbar?>

        <!-- Header band (keep your existing message) -->
        <div class="row border-bottom white-bg dashboard-header">
            <div class="col-xl-6">
                <h1>Visual Speech Practice Guide</h1>
                <span class="text-muted">Click on Exercises Below To Get Started</span>
            </div>
        </div>

        <!-- ===== Exercise UI goes here ===== -->
        <div class="wrapper wrapper-content animated fadeIn">
            <div class="row">
                <div class="col-lg-12">
                    <div class="ibox">
                        <div class="ibox-title">
                            <h5>Exercises Vowels, Consonants, Syllables</h5>
                        </div>
                        <div class="ibox-content">

                            <!-- Top category buttons -->
                            <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
                                <div class="btn-group flex-wrap" role="group">
                                    <button class="btn btn-primary category-btn" data-panel="panel-letters">Letter Sounds</button>
                                    <button class="btn btn-primary category-btn" data-panel="panel-cv">CV Blending</button>
                                    <button class="btn btn-primary category-btn" data-panel="panel-3cv">3CV</button>
                                </div>

                                <div class="btn-group flex-wrap" role="group">
                                    <button class="btn btn-info category-btn" data-panel="panel-soundmixing">Sound Mixing</button>
                                    <button class="btn btn-info category-btn" data-panel="panel-wordsyllable">Word/Syllable</button>
                                </div>



                                <div class="ms-auto d-flex align-items-center">
                                    <label for="exerciseCountSelect" class="me-2 mb-0 small text-muted">
                                        # Exercises
                                    </label>
                                    <select id="exerciseCountSelect" class="form-select form-select-sm" style="width: auto;">
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3" selected>3</option>
                                        <option value="4">4</option>
                                        <option value="5">5</option>
                                        <option value="6">6</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Slide-down selection panels -->
                            <div id="panel-letters" class="slide-panel">
                                <div id="lettersounds-list"></div>
                            </div>
                            <div id="panel-cv" class="slide-panel">
                                <div class="d-flex flex-wrap py-2" id="cv-list"></div>
                            </div>
                            <div id="panel-3cv" class="slide-panel">
                                <div class="d-flex flex-wrap py-2" id="3cv-list"></div>
                            </div>
                            <div id="panel-soundmixing" class="slide-panel">
                                <div class="d-flex flex-wrap py-2" id="soundmixing-list"></div>
                            </div>
                            <div id="panel-wordsyllable" class="slide-panel">
                                <div class="d-flex flex-wrap py-2" id="wordsyllable-list"></div>
                            </div>

                            <!-- Video button ABOVE main panel (full width) -->
                            <div class="mt-3">
                                <button class="btn btn-outline-primary w-100" id="btnVideo">
                                    <i class="fa fa-play-circle me-1"></i> Video Example
                                </button>
                                <div class="mt-2 small text-muted">
                                    Watch a quick demo of how to perform this exercise.
                                </div>
                            </div>

                            <!-- Main exercise panel FULL WIDTH -->
                            <div class="row mt-3 g-3">
                                <div class="col-12">
                                    <div class="exercise-frame">
                                        <div id="exercise-view" class="exercise-grid">
                                            <!-- JS will inject 6 .exercise-card elements here -->
                                            <div class="exercise-placeholder text-muted">
                                                Pick an exercise above
                                            </div>
                                        </div>
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
                                  <!--  <button class="nav-btn" id="btnBack" title="Previous">
                                        &#x25C0; <span>Back</span>
                                    </button>
                                    <button class="nav-btn" id="btnNext" title="Next">
                                        <span>Next</span> &#x25B6;
                                    </button> -->
                                </div>
                                <!-- Start disabled -->
                                <button class="success-btn" id="btnSuccess" disabled>
                                    <i class="fa fa-check-circle me-1"></i> Success
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ===== /Exercise UI ===== -->

        <div class="footer"></div>

    </div>
</div>

<!-- Modals -->
<div class="modal fade" id="videoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoTitle">Video Example</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="ratio ratio-16x9 border rounded">
                    <iframe id="videoPlayer"
                            src="https://www.youtube.com/embed/69DwHUg2f7s"
                            title="Vowel Sounds"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen>
                    </iframe>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content text-center p-4">
            <h4 class="mb-2">Nice job!</h4>
            <img src="img/success.jpg" onerror="this.style.display='none';" class="img-fluid mb-2" alt="Success">
            <p class="text-muted mb-0">You completed this exercise.</p>
        </div>
    </div>
</div>

<!-- Your existing JS bundle stack -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/metismenu/js/metisMenu.min.js"></script>
<script src="plugins/pace-js/js/pace.min.js"></script>
<script src="plugins/wow.js/js/wow.min.js"></script>
<script src="plugins/lucide/js/lucide.min.js"></script>
<script src="plugins/simplebar/js/simplebar.min.js"></script>
<script src="js/inspinia.js"></script>
<script src="plugins/flot/js/jquery.flot.js"></script>
<script src="plugins/jquery-flot-tooltip/js/jquery.flot.tooltip.min.js"></script>
<script src="plugins/flot-spline/js/jquery.flot.spline.js"></script>
<script src="plugins/jquery-flot-resize/js/index.js"></script>
<script src="plugins/peity/js/jquery.peity.min.js"></script>
<script src="js/demo/peity-demo.js"></script>
<script src="plugins/jquery-ui/js/jquery-ui.min.js"></script>
<script src="plugins/gritter/js/jquery.gritter.js"></script>
<script src="plugins/jquery-sparkline/js/jquery.sparkline.min.js"></script>
<script src="js/demo/sparkline-demo.js"></script>
<script src="plugins/chartjs/js/Chart.min.js"></script>
<script src="plugins/toastr/js/toastr.min.js"></script>

<script>
    /* -------- Mock data for POC (replace with API later) -------- */
    let soundMixAssignments = [];
    let currentExerciseItem = null;
    let wordAssignments      = [];
    let standardAssignments = [];
    let cardAssignments = [];      // one entry per card: {type, id} or null
    let selectedCardIndex = null;  // which card is “selected” for the next pill
    let cardSelectEnabled = true;


    let isSoundMixingMode = false;
    let isWordSyllableMode   = false;
    let isCVBlendingMode = false;

    const CONSONANTS = ["B","D","F","G","H","J","K","L","M","N","P","R","S","T","V","W","Y","Z"];
    const VOWELS = [
        {code:"AH", label:"AH"}, {code:"EE", label:"EE"}, {code:"OO", label:"OO"},{code:"OH", label:"OH"}

    ];
    const WORDS = [
        "apple","basket","bottle","bubble","bunny","button","cabin","camel","candy","cereal",
        "cookie","copper","cousin","cuddle","daddy","dizzy","donut","fire","flower","garden","gravy",
        "happy","jacket","jelly","jungle","jumpy","kitty","lion","little","magic","middle",
        "monkey","mommy","music","napkin","nibble","noodle","panda","pencil",
        "pickle","pillow","pizza","pocket","puddle","puppy","rainy","robot","rocket","soccer",
        "snowy","spider","sunny","tiger","ticket","tummy","turtle","wiggle","yellow","zigzag",
        "zipper"
    ];

    const CV_BLEND_ITEMS = [];

    CONSONANTS.forEach(c => {
        VOWELS.forEach(v => {
            CV_BLEND_ITEMS.push({
                c: c,
                v: v.code
            });
        });
    });


    // Simple linear sequence for prev/next (can be replaced by server-provided order)
    const SEQUENCE = [
        ...VOWELS.map(v => ({type:"vowel", id:v.code})),
        ...CONSONANTS.map(c => ({type:"consonant", id:c})),
        {type:"cv", id:"CH-AH"}, {type:"cv", id:"CH-EE"}, {type:"cv", id:"CH-OH"}
    ];
    const IMG_BASE = "/assets/portal/exercises/images/";

    function getWhole(item) {
        return item.c + ' + ' +item.v;
    }

    function getPartsText(item) {
        return `${item.c} + ${item.v}`;
    }



    function videoFor(item){ return ""; } // return real path later

    let currentIndex = -1;

    /* -------- Build selection lists -------- */
    function buttonPillImage(src, alt, onClick) {
        const el = document.createElement('div');
        el.className = 'pill';
        const img = document.createElement('img');
        img.src = src;
        img.alt = alt;
        img.style.width = "90px";
        img.style.height = "90px";
        img.style.objectFit = "contain";
        img.draggable = false;
        el.appendChild(img);
        el.addEventListener('click', onClick);
        return el;
    }

    function buttonPill(text, onClick) {
        const el = document.createElement('div');
        el.className = 'pill';
        el.textContent = text;
        el.addEventListener('click', onClick);
        return el;
    }


    function getExerciseCount() {
        const sel = document.getElementById('exerciseCountSelect');
        if (!sel) return 3; // default fallback
        const n = parseInt(sel.value, 10);
        return (isNaN(n) ? 3 : Math.min(Math.max(n, 1), 6));
    }
    function addListHeader($parent, text) {
        const h = document.createElement('div')
        h.className = 'pill-section-header'
        h.textContent = text
        $parent.appendChild(h)
    }

    function buildLists() {
        const basePath = "/assets/portal/exercises/images/"

        const $letters = document.getElementById('lettersounds-list')
        const $cv      = document.getElementById('cv-list')
        const $3cv     = document.getElementById('3cv-list')
        const $sm      = document.getElementById('soundmixing-list')
        const $ws      = document.getElementById('wordsyllable-list')

        // ---- Clear lists to avoid duplicates ----
        if ($letters) $letters.innerHTML = ''
        if ($cv)      $cv.innerHTML      = ''
        if ($3cv)     $3cv.innerHTML     = ''
        if ($sm)      $sm.innerHTML      = ''
        if ($ws)      $ws.innerHTML      = ''

        // ---- Letter Sounds: Consonants then Vowels ----
        // ---- Letter Sounds: Consonants then Vowels ----
        if ($letters) {
            addListHeader($letters, 'Consonants')

            CONSONANTS.forEach(c => {
                const src = `${basePath}consonant_${c}.png`
                $letters.appendChild(
                    buttonPillImage(src, c, () => {
                        handleItemSelection({ type: "consonant", id: c })
                        // DO NOT close panel here
                    })
                )
            })

            addListHeader($letters, 'Vowels')

            VOWELS.forEach(v => {
                const src = `${basePath}vowel_${v.code}.png`
                $letters.appendChild(
                    buttonPillImage(src, v.label, () => {
                        handleItemSelection({ type: "vowel", id: v.code })
                        // DO NOT close panel here
                    })
                )
            })
        }


        // ---- CV Blending list (your NEW behavior) ----
        if ($cv) {
            CV_BLEND_ITEMS.forEach(item => {
                const label = getWhole(item) // "BOO"
                $cv.appendChild(
                    buttonPill(label, () => {
                        renderBlendingExercise(item.c, item.v, 'cv')
                        document.querySelectorAll('.slide-panel').forEach(p => p.classList.remove('open'))
                    })
                )
            })
        }

        // ---- 3CV list (your NEW behavior: BOO BOO PICTURE) ----
        if ($3cv) {
            // Use same combos as before: CONSONANTS x VOWELS (limited)
            CONSONANTS.slice(0, 18).forEach(c => {
                VOWELS.slice(0, 4).forEach(v => {
                    const label = `${c}${v.code}`  // "BOO" style label
                    $3cv.appendChild(
                        buttonPill(label, () => {
                            renderBlendingExercise(c, v.code, '3cv')
                            document.querySelectorAll('.slide-panel').forEach(p => p.classList.remove('open'))
                        })
                    )
                })
            })
        }

        // ---- Sound Mixing list (keep your existing selection flow) ----
        if ($sm) {
            CONSONANTS.slice(0, 18).forEach(c => {
                VOWELS.slice(0, 4).forEach(v => {
                    const label = `${c}-${v.code}`
                    $sm.appendChild(
                        buttonPill(label, () => {
                            handleItemSelection({ type: "cv", id: label })
                            document.querySelectorAll('.slide-panel').forEach(p => p.classList.remove('open'))
                        })
                    )
                })
            })
        }

        // ---- Word / syllable ----
        if ($ws) {
            WORDS.forEach(w => {
                $ws.appendChild(
                    buttonPill(w, () => {
                        handleItemSelection({ type: "word", id: w })
                        document.querySelectorAll('.slide-panel').forEach(p => p.classList.remove('open'))
                    })
                )
            })
        }
    }


    // icon file path (you already use this pattern in the lists)
    function iconPathFor(item) {
        if (item.type === "vowel")     return `${IMG_BASE}vowel_${item.id}.png`;
        if (item.type === "consonant") return `${IMG_BASE}consonant_${item.id}.png`;
        // CV ids are like "S-AH" -> filename uses underscore
        if (item.type === "cv")        return `${IMG_BASE}cv_${item.id.replace('-', '_')}.jpg`;
        if (item.type === "3cv")        return `${IMG_BASE}3cv_${item.id.replace('-', '_')}.jpg`;
        return "";
    }

    // top image path (new)
    function topPathFor(item) {
        if (item.type === "vowel")     return `${IMG_BASE}top_vowel_${item.id}.png`;
        if (item.type === "consonant") return `${IMG_BASE}top_consonant_${item.id}.png`;
        if (item.type === "cv")        return `${IMG_BASE}top_cv_${item.id.replace('-', '_')}.jpg`;
        if (item.type === "3cv")        return `${IMG_BASE}top_3cv_${item.id.replace('-', '_')}.jpg`;
        return "";
    }

    async function renderCardAtIndex(index, item) {
        const grid  = document.getElementById('exercise-view');
        if (!grid) return;

        const cards   = grid.querySelectorAll('.exercise-card');
        const card    = cards[index];
        if (!card) return;

        const content = card.querySelector('.exercise-content');
        if (!content) return;

        content.innerHTML = '';
        card.classList.remove('completed');

        const label = (item.type === 'word') ? item.id : prettyLabel(item);

        // --- Consonant / vowel ---
        if (item.type === 'consonant' || item.type === 'vowel') {
            let topSrc  = topPathFor(item);
            let iconSrc = iconPathFor(item);

            const hasTop  = await loadImage(topSrc);
            const hasIcon = await loadImage(iconSrc);

            if (hasTop) {
                const topImg = document.createElement('img');
                topImg.src = topSrc;
                topImg.alt = label;
                topImg.className = 'exercise-main-img';
                content.appendChild(topImg);
            } else {
                const span = document.createElement('span');
                span.textContent = label;
                content.appendChild(span);
            }

            if (hasIcon) {
                const iconImg = document.createElement('img');
                iconImg.src = iconSrc;
                iconImg.alt = label + " icon";
                iconImg.className = 'exercise-icon-img';
                content.appendChild(iconImg);
            }

            return;
        }

        // --- Word / syllable ---
        if (item.type === 'word') {
            const word    = item.id;
            const topBase = `${IMG_BASE}top_${word.toLowerCase()}`;
            const topSrc  = await firstExistingImage(topBase);

            if (topSrc) {
                const topImg = document.createElement('img');
                topImg.src = topSrc;
                topImg.alt = word;
                topImg.className = 'exercise-main-img';
                content.appendChild(topImg);
            } else {
                const span = document.createElement('span');
                span.textContent = word;
                content.appendChild(span);
            }

            const left  = word.slice(0, 2);
            const right = word.slice(2);

            const bottomRow = document.createElement('div');
            bottomRow.className = 'word-bottom-row';

            const leftSpan = document.createElement('span');
            leftSpan.className = 'word-part';
            leftSpan.textContent = left;

            const plusSpan = document.createElement('span');
            plusSpan.className = 'word-plus';
            plusSpan.textContent = '+';

            const rightSpan = document.createElement('span');
            rightSpan.className = 'word-part';
            rightSpan.textContent = right;

            bottomRow.appendChild(leftSpan);
            bottomRow.appendChild(plusSpan);
            bottomRow.appendChild(rightSpan);

            content.appendChild(bottomRow);
            return;
        }

        // --- CV / 3CV (including sound mixing) ---
        if (item.type === 'cv' || item.type === '3cv') {
            const [cons, vowelCode] = item.id.split('-');
            const baseId = `${cons}_${vowelCode}`;

            let topBase = `${IMG_BASE}top_cv_${baseId}`;
            if (item.type === '3cv') {
                topBase = `${IMG_BASE}top_3cv_${baseId}`;
            }

            const topSrc   = await firstExistingImage(topBase);
            const consBase = `${IMG_BASE}consonant_${cons}`;
            const vowelBase= `${IMG_BASE}vowel_${vowelCode}`;

            const consSrc  = await firstExistingImage(consBase);
            const vowelSrc = await firstExistingImage(vowelBase);

            if (topSrc) {
                const topImg = document.createElement('img');
                topImg.src = topSrc;
                topImg.alt = label;
                topImg.className = 'exercise-main-img';
                content.appendChild(topImg);
            } else {
                const span = document.createElement('span');
                span.textContent = label;
                content.appendChild(span);
            }

            const bottomRow = document.createElement('div');
            bottomRow.className = 'cv-bottom-row';

            if (consSrc) {
                const cImg = document.createElement('img');
                cImg.src = consSrc;
                cImg.alt = `Consonant ${cons}`;
                bottomRow.appendChild(cImg);
            } else {
                const cText = document.createElement('span');
                cText.textContent = cons;
                bottomRow.appendChild(cText);
            }

            if (vowelSrc) {
                const vImg = document.createElement('img');
                vImg.src = vowelSrc;
                vImg.alt = `Vowel ${vowelCode}`;
                bottomRow.appendChild(vImg);
            } else {
                const vText = document.createElement('span');
                vText.textContent = vowelCode;
                bottomRow.appendChild(vText);
            }

            content.appendChild(bottomRow);
            return;
        }

        // Fallback: just show label
        const span = document.createElement('span');
        span.textContent = label;
        content.appendChild(span);
    }


    function resetExerciseArea() {
        const grid       = document.getElementById('exercise-view');
        const btnSuccess = document.getElementById('btnSuccess');
        const title      = document.getElementById('choices-title');
        const wrap       = document.getElementById('choices-wrap');

        standardAssignments = [];

        // Reset main grid with a placeholder
        if (grid) {
            if (isCVBlendingMode) {
                const grid = document.getElementById('exercise-view');
                if (grid) grid.innerHTML = '';
            } else {
                grid.innerHTML = `
                <div class="exercise-placeholder text-muted">
                    Pick an exercise above
                </div>
            `;
            }

        }



        // Disable success button
        if (btnSuccess) {
            btnSuccess.disabled = true;
        }

        // Clear dynamic choices area
        if (title) {
            title.style.display = 'none';
            title.textContent   = '';
        }
        if (wrap) {
            wrap.innerHTML = '';
        }

        // Reset state
        currentExerciseItem  = null;
        currentIndex         = -1;
        soundMixAssignments  = [];
        wordAssignments      = [];
    }


    // image existence check (client-side) via onload/onerror
    function loadImage(src) {
        return new Promise(resolve => {
            if (!src) return resolve(false);
            const img = new Image();
            img.onload = () => resolve(true);
            img.onerror = () => resolve(false);
            img.src = src + `?v=${Date.now()}`; // bust cache during POC
        });
    }

    async function selectSoundMix(item) {
        const grid       = document.getElementById('exercise-view');
        const btnSuccess = document.getElementById('btnSuccess');
        if (!grid) return;

        isSoundMixingMode  = true;
        isWordSyllableMode = false;

        // Hide bottom choices bar in this mode
        const title = document.getElementById('choices-title');
        const wrap  = document.getElementById('choices-wrap');
        if (title) {
            title.style.display = 'none';
            title.textContent   = '';
        }
        if (wrap) wrap.innerHTML = '';

        const count = getExerciseCount();

        // Initialize / reset grid if needed
        if (!soundMixAssignments || soundMixAssignments.length !== count || grid.children.length !== count) {
            soundMixAssignments = new Array(count).fill(null);
            grid.innerHTML = "";

            if (btnSuccess) btnSuccess.disabled = true;

            for (let i = 0; i < count; i++) {

                // --- Build the card box ---
                const card = document.createElement('div');
                card.className = 'exercise-card';

                const content = document.createElement('div');
                content.className = 'exercise-content';

                const span = document.createElement('span');
                span.textContent = 'Choose sound';
                span.classList.add('text-muted');
                content.appendChild(span);

                card.appendChild(content);

                // --- Build the actions container (OUTSIDE the card) ---
                const actions = document.createElement('div');
                actions.className = 'exercise-card-actions';

                const changeBtn = document.createElement('button');
                changeBtn.type = 'button';
                changeBtn.className = 'change-card-btn';
                changeBtn.textContent = 'Change Card';

                changeBtn.addEventListener('click', function (e) {
                    e.stopPropagation();       // Prevent marking as completed
                    handleChangeCard(card);    // Reset this card only
                });

                actions.appendChild(changeBtn);

                // --- Append BOTH to the grid as siblings ---
                grid.appendChild(card);
                card.appendChild(actions);

                // --- Enable click behavior for completion ---
                wireExerciseCardClick(card);
            }

        }

        // Find first empty slot
        let idx = soundMixAssignments.findIndex(x => x === null);
        if (idx === -1) {
            // All full — overwrite the last card (or return; your choice)
            idx = count - 1;
        }

        soundMixAssignments[idx] = item;

        const [cons, vowelCode] = item.id.split('-');
        const baseId   = `${cons}_${vowelCode}`;
        let   topBase  = `${IMG_BASE}top_cv_${baseId}`;
        if (item.type === "3cv") {
            topBase = `${IMG_BASE}top_3cv_${baseId}`;
        }

        const topSrc   = await firstExistingImage(topBase);
        const consBase = `${IMG_BASE}consonant_${cons}`;
        const vowelBase= `${IMG_BASE}vowel_${vowelCode}`;
        const consSrc  = await firstExistingImage(consBase);
        const vowelSrc = await firstExistingImage(vowelBase);

        const label = prettyLabel(item);

        const cards   = grid.querySelectorAll('.exercise-card');
        const card    = cards[idx];
        const content = card.querySelector('.exercise-content');
        content.innerHTML = "";

        // Top image or label
        if (topSrc) {
            const topImg = document.createElement('img');
            topImg.src = topSrc;
            topImg.alt = label;
            topImg.className = 'exercise-main-img';
            content.appendChild(topImg);
        } else {
            const span = document.createElement('span');
            span.textContent = label;
            content.appendChild(span);
        }

        // Bottom row: consonant + vowel images
        const bottomRow = document.createElement('div');
        bottomRow.className = 'cv-bottom-row';

        if (consSrc) {
            const cImg = document.createElement('img');
            cImg.src = consSrc;
            cImg.alt = `Consonant ${cons}`;
            bottomRow.appendChild(cImg);
        } else {
            const cText = document.createElement('span');
            cText.textContent = cons;
            bottomRow.appendChild(cText);
        }

        if (vowelSrc) {
            const vImg = document.createElement('img');
            vImg.src = vowelSrc;
            vImg.alt = `Vowel ${vowelCode}`;
            bottomRow.appendChild(vImg);
        } else {
            const vText = document.createElement('span');
            vText.textContent = vowelCode;
            bottomRow.appendChild(vText);
        }

        content.appendChild(bottomRow);
    }



    async function firstExistingImage(basePath) {
        const exts = ['.png', '.jpg', '.jpeg'];

        for (const ext of exts) {
            const url = basePath + ext;
            const ok = await loadImage(url);
            if (ok) {
                return url; // this one exists
            }
        }
        return null; // none worked
    }



    /* -------- Panel toggle -------- */

    let activePanelId = null


    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.category-btn')
        if (!btn) return

        const target = btn.dataset.panel
        const targetEl = document.getElementById(target)
        if (!targetEl) return

        const isSwitchingPanels = (activePanelId !== target)

        // Toggle the target panel, close others
        document.querySelectorAll('.slide-panel').forEach(p => {
            if (p.id === target) p.classList.toggle('open')
            else p.classList.remove('open')
        })

        // If we switched panels, update modes + reset
        if (isSwitchingPanels) {
            activePanelId = target

            isSoundMixingMode  = (target === 'panel-soundmixing')
            isWordSyllableMode = (target === 'panel-wordsyllable')
            isCVBlendingMode   = (target === 'panel-cv')

            cardSelectEnabled = !isCVBlendingMode
            selectedCardIndex = null

            resetExerciseArea()
            document.querySelectorAll('.exercise-card').forEach(c => c.classList.remove('selected'))
        }
    })

    /* -------- Selection and render -------- */
    function selectByObject(obj){
        let idx = SEQUENCE.findIndex(x => x.type===obj.type && x.id===obj.id);
        if (idx === -1) { SEQUENCE.push(obj); idx = SEQUENCE.length-1; }
        currentIndex = idx;
        renderExercise(SEQUENCE[currentIndex]);
        document.querySelectorAll('.slide-panel').forEach(p=>p.classList.remove('open'));
    }

    async function renderExercise(item) {
        const grid       = document.getElementById('exercise-view');
        const btnSuccess = document.getElementById('btnSuccess');

        const label = prettyLabel(item);
        const NUMBER_OF_CARDS = getExerciseCount();

        if (!grid) return;

        // We're in the "standard" mode (not sound mixing, not word/syllable)

        // Remember what we're showing, so the dropdown can re-render it
        currentExerciseItem = item;

        // New exercise: clear grid and require all cards again
        grid.innerHTML = "";
        if (btnSuccess) {
            btnSuccess.disabled = true;
        }

        standardAssignments = new Array(NUMBER_OF_CARDS).fill(item);




        for (let i = 0; i < NUMBER_OF_CARDS; i++) {
            const card = document.createElement('div');
            card.className = 'exercise-card';

            // Content area
            const content = document.createElement('div');
            content.className = 'exercise-content';

            // ---------- TOP IMAGE OR BIG LABEL ----------
            let topSrc, iconSrc, hasTop = false, hasIcon = false;
            let cvConsonantSrc = null;
            let cvVowelSrc     = null;

            if (item.type === "cv" || item.type === "3cv") {

                if (item.type === "cv") {
                    var cons = item.id.slice(0,1)
                    var vowelCode = item.id.slice(1,3)

                    var baseId            = `${cons}_${vowelCode}`;
                } else {
                    var [cons, vowelCode] = item.id.split('-');
                    var baseId            = `${cons}_${vowelCode}`;
                }





                let topBase = `${IMG_BASE}top_cv_${baseId}`;
                if (item.type === "3cv") {
                    topBase = `${IMG_BASE}top_3cv_${baseId}`;
                }

                topSrc  = await firstExistingImage(topBase);
                hasTop  = !!topSrc;

                const consBase   = `${IMG_BASE}consonant_${cons}`;
                const vowelBase  = `${IMG_BASE}vowel_${vowelCode}`;
                cvConsonantSrc   = await firstExistingImage(consBase);
                cvVowelSrc       = await firstExistingImage(vowelBase);
                hasIcon          = !!(cvConsonantSrc || cvVowelSrc);
            } else {
                topSrc  = topPathFor(item);
                iconSrc = iconPathFor(item);

                hasTop  = await loadImage(topSrc);
                hasIcon = await loadImage(iconSrc);
            }

            if (hasTop) {
                const topImg = document.createElement('img');
                topImg.src = topSrc;
                topImg.alt = label;
                topImg.className = 'exercise-main-img';
                content.appendChild(topImg);
            } else {
                const span = document.createElement('span');
                span.textContent = label;
                content.appendChild(span);
            }

            // ---------- BOTTOM: ICON OR CV COMBO ----------
            if (item.type === "cv" || item.type === "3cv") {
                const bottomRow = document.createElement('div');
                bottomRow.className = 'cv-bottom-row';

                if (cvConsonantSrc) {
                    const cImg = document.createElement('img');
                    cImg.src = cvConsonantSrc;
                    cImg.alt = `Consonant ${label}`;
                    bottomRow.appendChild(cImg);
                } else {
                    const cText = document.createElement('span');
                    cText.textContent = label.split(' + ')[0] || item.id.split('-')[0];
                    bottomRow.appendChild(cText);
                }

                if (cvVowelSrc) {
                    const vImg = document.createElement('img');
                    vImg.src = cvVowelSrc;
                    vImg.alt = `Vowel ${label}`;
                    bottomRow.appendChild(vImg);
                } else {
                    const vText = document.createElement('span');
                    const parts = item.id.split('-');
                    vText.textContent = parts[1] || '';
                    bottomRow.appendChild(vText);
                }

                content.appendChild(bottomRow);
            } else {
                if (hasIcon) {
                    const iconImg = document.createElement('img');
                    iconImg.src = iconSrc;
                    iconImg.alt = label + " icon";
                    iconImg.className = 'exercise-icon-img';
                    content.appendChild(iconImg);
                } else {
                    const mini = document.createElement('div');
                    mini.textContent = label;
                    mini.style.opacity = ".6";
                    mini.style.fontSize = "18px";
                    content.appendChild(mini);
                }
            }

            // ---------- Actions area: Change Card button ----------
            const actions = document.createElement('div');
            actions.className = 'exercise-card-actions';

            const changeBtn = document.createElement('button');
            changeBtn.type = 'button';
// Use ONLY our custom class so our CSS wins
            changeBtn.className = 'change-card-btn';
            changeBtn.textContent = 'Change Card';
            changeBtn.addEventListener('click', function (e) {
                e.stopPropagation();        // don’t mark card complete
                handleChangeCard(card);
            });

            actions.appendChild(changeBtn);

// Append content to the card
            card.appendChild(content);

// Append card AND button as siblings to the grid
            grid.appendChild(card);
            card.appendChild(actions);

// Wire behavior for completion
            wireExerciseCardClick(card);


        }

        // set video (kept from your earlier logic)
        const vsrc = videoFor(item);
        const vp   = document.getElementById('videoPlayer');
        // if (vp) vp.src = vsrc;

        // dynamic choices (unchanged)
        const title = document.getElementById('choices-title');
        const wrap  = document.getElementById('choices-wrap');
        wrap.innerHTML = "";

        if (item.type === "vowel") {
            title.style.display = "";
            title.textContent = "Pick a consonant to blend with this vowel:";
            CONSONANTS.forEach(c =>
                wrap.appendChild(
                    buttonPill(c, () => selectByObject({ type: "cv", id: `${c}-${item.id}` }))
                )
            );
        } else if (item.type === "consonant") {
            title.style.display = "";
            title.textContent = "Pick a vowel to blend with this consonant:";
            VOWELS.forEach(v =>
                wrap.appendChild(
                    buttonPill(v.label, () => selectByObject({ type: "cv", id: `${item.id}-${v.code}` }))
                )
            );
        } else {
            title.style.display = "none";
        }

        $('#btnBack').toggleClass('ghost', currentIndex <= 0);
        $('#btnNext').toggleClass('ghost', currentIndex >= SEQUENCE.length - 1);
    }

    async function renderBlendingExercise(cons, vowelCode, mode = 'cv') {
        const grid       = document.getElementById('exercise-view');
        const btnSuccess = document.getElementById('btnSuccess');
        if (!grid) return;

        const count = getExerciseCount();

        // Lock mode
        isSoundMixingMode  = false;
        isWordSyllableMode = false;
        isCVBlendingMode   = true;
        cardSelectEnabled  = false;
        selectedCardIndex  = null;

        grid.innerHTML = '';
        if (btnSuccess) btnSuccess.disabled = true;

        const whole = `${cons}${vowelCode}`;      // "BOO"
        const parts = (mode === 'cv')
            ? `${cons} + ${vowelCode}`            // CV: "B + OO"
            : whole;                              // 3CV: "BOO"

        // Picture for the final card only
        const baseId    = `${cons}_${vowelCode}`; // B_OO
        const imageBase = (mode === '3cv')
            ? `${IMG_BASE}top_3cv_${baseId}`
            : `${IMG_BASE}top_cv_${baseId}`;

        const wholeSrc = await firstExistingImage(imageBase);

        for (let i = 0; i < count; i++) {
            const card = document.createElement('div');
            card.className = 'exercise-card';

            const content = document.createElement('div');
            content.className = 'exercise-content';

            // First N-1 cards: TEXT ONLY
            if (i < count - 1) {
                const span = document.createElement('span');
                span.className = 'cv-parts-text'; // reuse your big/bold style
                span.textContent = parts;
                content.appendChild(span);
            }
            // Last card: picture (fallback to text)
            else {
                if (wholeSrc) {
                    const img = document.createElement('img');
                    img.src = wholeSrc;
                    img.alt = whole;
                    img.className = 'exercise-main-img';
                    content.appendChild(img);
                } else {
                    const span = document.createElement('span');
                    span.className = 'cv-whole-text';
                    span.textContent = whole;
                    content.appendChild(span);
                }
            }

            card.appendChild(content);
            wireExerciseCardClick(card);
            grid.appendChild(card);
        }

        // Hide bottom choices bar
        const title = document.getElementById('choices-title');
        const wrap  = document.getElementById('choices-wrap');
        if (title) { title.style.display = 'none'; title.textContent = ''; }
        if (wrap) wrap.innerHTML = '';
    }



    async function renderSingleStandardCard(card, item) {
        const content = card.querySelector('.exercise-content');
        if (!content) return;

        content.innerHTML = "";

        const label = prettyLabel(item);

        let topSrc, iconSrc;
        let hasTop = false, hasIcon = false;

        if (item.type === "cv" || item.type === "3cv") {
            const [cons, vowelCode] = item.id.split('-');
            const baseId = `${cons}_${vowelCode}`;

            let topBase = `${IMG_BASE}top_cv_${baseId}`;
            if (item.type === "3cv") topBase = `${IMG_BASE}top_3cv_${baseId}`;

            topSrc = await firstExistingImage(topBase);
            hasTop = !!topSrc;

            const consBase  = `${IMG_BASE}consonant_${cons}`;
            const vowelBase = `${IMG_BASE}vowel_${vowelCode}`;

            const consSrc  = await firstExistingImage(consBase);
            const vowelSrc = await firstExistingImage(vowelBase);

            const bottomRow = document.createElement('div');
            bottomRow.className = 'cv-bottom-row';

            if (consSrc) {
                const img = document.createElement('img');
                img.src = consSrc;
                bottomRow.appendChild(img);
            }

            if (vowelSrc) {
                const img = document.createElement('img');
                img.src = vowelSrc;
                bottomRow.appendChild(img);
            }

            if (hasTop) {
                const img = document.createElement('img');
                img.src = topSrc;
                img.className = "exercise-main-img";
                content.appendChild(img);
            } else {
                content.appendChild(document.createTextNode(label));
            }

            content.appendChild(bottomRow);
            return;
        }

        // Consonant / vowel normal case:
        topSrc  = topPathFor(item);
        iconSrc = iconPathFor(item);

        hasTop  = await loadImage(topSrc);
        hasIcon = await loadImage(iconSrc);

        if (hasTop) {
            const img = document.createElement('img');
            img.src = topSrc;
            img.className = "exercise-main-img";
            content.appendChild(img);
        } else {
            content.appendChild(document.createTextNode(label));
        }

        if (hasIcon) {
            const img = document.createElement('img');
            img.src = iconSrc;
            img.className = "exercise-icon-img";
            content.appendChild(img);
        }
    }


    async function selectWordExercise(word) {
        const grid       = document.getElementById('exercise-view');
        const btnSuccess = document.getElementById('btnSuccess');
        if (!grid) return;

        isSoundMixingMode  = false;
        isWordSyllableMode = true;

        // Hide bottom choices bar
        const title = document.getElementById('choices-title');
        const wrap  = document.getElementById('choices-wrap');
        if (title) {
            title.style.display = 'none';
            title.textContent   = '';
        }
        if (wrap) wrap.innerHTML = '';

        const count = getExerciseCount();

        // Initialize / reset grid if needed
        if (!wordAssignments || wordAssignments.length !== count || grid.children.length !== count) {
            wordAssignments = new Array(count).fill(null);
            grid.innerHTML = "";

            if (btnSuccess) btnSuccess.disabled = true;

            for (let i = 0; i < count; i++) {
                const card = document.createElement('div');
                card.className = 'exercise-card';

                const content = document.createElement('div');
                content.className = 'exercise-content';

                const span = document.createElement('span');
                span.textContent = 'Choose word';
                span.classList.add('text-muted');
                content.appendChild(span);

                // Actions container OUTSIDE the card
                const actions = document.createElement('div');
                actions.className = 'exercise-card-actions';

                const changeBtn = document.createElement('button');
                changeBtn.type = 'button';
                changeBtn.className = 'change-card-btn';
                changeBtn.textContent = 'Change Card';
                changeBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    handleChangeCard(card);
                });

                actions.appendChild(changeBtn);

                // Card contains only the content
                card.appendChild(content);

                // Append card and button as siblings
                grid.appendChild(card);
                card.appendChild(actions);

                wireExerciseCardClick(card);
            }

        }

        // Find first empty slot
        let idx = wordAssignments.findIndex(x => x === null);
        if (idx === -1) {
            // All full – overwrite the last one (or return; your choice)
            idx = count - 1;
        }

        wordAssignments[idx] = word;

        // Resolve top image: top_<word>.(png|jpg|jpeg)
        const topBase = `${IMG_BASE}top_${word.toLowerCase()}`;
        const topSrc  = await firstExistingImage(topBase);

        const label   = word;
        const left    = word.slice(0, 2);
        const right   = word.slice(2);

        const cards   = grid.querySelectorAll('.exercise-card');
        const card    = cards[idx];
        const content = card.querySelector('.exercise-content');
        content.innerHTML = "";

        // Top image or fallback label
        if (topSrc) {
            const topImg = document.createElement('img');
            topImg.src = topSrc;
            topImg.alt = label;
            topImg.className = 'exercise-main-img';
            content.appendChild(topImg);
        } else {
            const span = document.createElement('span');
            span.textContent = label;
            content.appendChild(span);
        }

        // Bottom text: "pu + ppy"
        const bottomRow = document.createElement('div');
        bottomRow.className = 'word-bottom-row';

        const leftSpan = document.createElement('span');
        leftSpan.className = 'word-part';
        leftSpan.textContent = left;

        const plusSpan = document.createElement('span');
        plusSpan.className = 'word-plus';
        plusSpan.textContent = '+';

        const rightSpan = document.createElement('span');
        rightSpan.className = 'word-part';
        rightSpan.textContent = right;

        bottomRow.appendChild(leftSpan);
        bottomRow.appendChild(plusSpan);
        bottomRow.appendChild(rightSpan);

        content.appendChild(bottomRow);
    }




    function prettyLabel(item){
        if (item.type==='cv') return item.id.replace('-', ' + ');
        return item.id;
    }

    /* -------- Nav + Modals -------- */
    $('#btnBack').on('click', function(){ if (currentIndex>0){ currentIndex--; renderExercise(SEQUENCE[currentIndex]); }});
    $('#btnNext').on('click', function(){ if (currentIndex<SEQUENCE.length-1){ currentIndex++; renderExercise(SEQUENCE[currentIndex]); }});

    $('#btnVideo').on('click', function () {
        new bootstrap.Modal('#videoModal').show();
    });

    // Success button: JUST show the modal, no auto-advance, no reset.
    $('#btnSuccess').on('click', function () {
        new bootstrap.Modal('#successModal').show();
    });

    const successModalEl = document.getElementById('successModal');
    if (successModalEl) {
        successModalEl.addEventListener('hidden.bs.modal', function () {
            // Intentionally empty – do NOT call renderExercise or reset grid.
            // Cards, selection, and assignments stay as-is.
        });
    }


    function initExerciseGrid() {
        const grid       = document.getElementById('exercise-view');
        const btnSuccess = document.getElementById('btnSuccess');
        if (!grid) return;

        const count = getExerciseCount();

        cardAssignments    = new Array(count).fill(null);
        selectedCardIndex  = null;
        grid.innerHTML     = '';

        for (let i = 0; i < count; i++) {
            const card = document.createElement('div');
            card.className = 'exercise-card';

            // Inner box that holds the content
            const box = document.createElement('div');
            box.className = 'exercise-box';

            const content = document.createElement('div');
            content.className = 'exercise-content';

            const span = document.createElement('span');
            span.textContent = 'Choose sound';
            span.classList.add('text-muted');
            content.appendChild(span);

            box.appendChild(content);
            card.appendChild(box);

            // "Select Card" button (renamed from Change Card)
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'change-card-btn';
            btn.textContent = 'Select Card';

            (function(index) {
                // Select this card for the next pill
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    setSelectedCard(index);
                });

                // Clicking the card itself marks it complete (same as before)
                box.addEventListener('click', function () {
                    const btnSuccess = document.getElementById('btnSuccess');

                    // must have something assigned before we can complete
                    if (!cardAssignments[index]) return;
                    if (card.classList.contains('completed')) return;

                    card.classList.add('completed');

                    const remaining = document.querySelectorAll('#exercise-view .exercise-card:not(.completed)').length;
                    if (remaining === 0 && btnSuccess) {
                        btnSuccess.disabled = false;
                    }
                });
            })(i);

            card.appendChild(btn);
            grid.appendChild(card);
        }

        if (btnSuccess) btnSuccess.disabled = true;
    }

    function setSelectedCard(index) {
        selectedCardIndex = index;

        const cards = document.querySelectorAll('#exercise-view .exercise-card');
        cards.forEach((c, i) => {
            if (i === index) c.classList.add('selected');
            else c.classList.remove('selected');
        });

        // Clear this card's assignment and UI back to placeholder
        cardAssignments[index] = null;

        const card = cards[index];
        if (!card) return;

        card.classList.remove('completed');

        const content = card.querySelector('.exercise-content');
        if (content) {
            content.innerHTML = '';
            const span = document.createElement('span');
            span.textContent = 'Choose sound';
            span.classList.add('text-muted');
            content.appendChild(span);
        }

        const btnSuccess = document.getElementById('btnSuccess');
        if (btnSuccess) btnSuccess.disabled = true;
    }

    function handleItemSelection(item) {
        if (isCVBlendingMode) return;
        const grid = document.getElementById('exercise-view');
        if (!grid) return;

        const desiredCount = getExerciseCount();

        // If the user changed the dropdown, rebuild grid
        if (cardAssignments.length !== desiredCount ||
            grid.querySelectorAll('.exercise-card').length !== desiredCount) {
            initExerciseGrid();
        }

        let index = (selectedCardIndex !== null)
            ? selectedCardIndex
            : cardAssignments.findIndex(x => x === null);

        if (index === -1) {
            // all full – pick first card
            index = 0;
        }

        cardAssignments[index] = item;
        renderCardAtIndex(index, item);
    }


    /* -------- Init -------- */
    buildLists();
    initExerciseGrid();  // start with blank cards


    function wireExerciseCardClick(card) {
        const btnSuccess = document.getElementById('btnSuccess');
        if (!card) return;

        card.addEventListener('click', function () {
            // Already completed? Do nothing.
            if (card.classList.contains('completed')) return;

            card.classList.add('completed');

            // Count how many cards are still not completed
            const remaining = document.querySelectorAll('#exercise-view .exercise-card:not(.completed)').length;

            // If none left, enable Success
            if (remaining === 0 && btnSuccess) {
                btnSuccess.disabled = false;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const sel = document.getElementById('exerciseCountSelect');
        if (!sel) return;

        sel.addEventListener('change', function () {
            initExerciseGrid();
        });

    });

    function getCardIndex(card) {
        const grid  = document.getElementById('exercise-view');
        if (!grid) return -1;
        const cards = Array.from(grid.querySelectorAll('.exercise-card'));
        return cards.indexOf(card);
    }

    /**
     * Change Card behavior:
     * - For "standard" modes (consonant/vowel/CV/3CV): just clear completion
     *   and disable Success so they can redo it.
     * - For Sound Mixing: put the slot back to "Choose sound" (no assignment).
     * - For Word/Syllable: put the slot back to "Choose word" (no assignment).
     * This does NOT count toward exercise completion.
     */
    function handleChangeCard(card) {
        const btnSuccess = document.getElementById('btnSuccess');
        const idx        = getCardIndex(card);
        if (idx === -1) return;

        // Clear completion state for this card
        card.classList.remove('completed');

        // As soon as ANY card is changed, Success should no longer be enabled
        if (btnSuccess) {
            btnSuccess.disabled = true;
        }

        const content = card.querySelector('.exercise-content');
        if (!content) return;

        if (isSoundMixingMode) {
            // Clear this assignment and show placeholder
            if (soundMixAssignments && soundMixAssignments.length > idx) {
                soundMixAssignments[idx] = null;
            }

            content.innerHTML = "";
            const span = document.createElement('span');
            span.textContent = 'Choose sound';
            span.classList.add('text-muted');
            content.appendChild(span);

        } else if (isWordSyllableMode) {
            // Clear this assignment and show placeholder
            if (wordAssignments && wordAssignments.length > idx) {
                wordAssignments[idx] = null;
            }

            content.innerHTML = "";
            const span = document.createElement('span');
            span.textContent = 'Choose word';
            span.classList.add('text-muted');
            content.appendChild(span);

        } else {
            if (currentExerciseItem && standardAssignments.length > idx) {

                let newItem = null;

                // Pick a random consonant (but not the same one)
                if (currentExerciseItem.type === "consonant") {
                    const options = CONSONANTS.filter(x => x !== standardAssignments[idx].id);
                    const choice = options[Math.floor(Math.random() * options.length)];
                    newItem = {type: "consonant", id: choice};
                }

                // Pick a random vowel
                if (currentExerciseItem.type === "vowel") {
                    const options = VOWELS.filter(v => v.code !== standardAssignments[idx].id);
                    const choice = options[Math.floor(Math.random() * options.length)];
                    newItem = {type: "vowel", id: choice.code};
                }

                // Pick a random CV
                if (currentExerciseItem.type === "cv") {
                    const [cons, vow] = currentExerciseItem.id.split("-");

                    const options = [];
                    CONSONANTS.forEach(c =>
                        VOWELS.forEach(v =>
                            options.push({type:"cv", id:`${c}-${v.code}`})
                        )
                    );

                    // remove the current assignment
                    const filtered = options.filter(o => o.id !== standardAssignments[idx].id);

                    newItem = filtered[Math.floor(Math.random() * filtered.length)];
                }

                // Pick a random 3CV (same logic but using 3cv)
                if (currentExerciseItem.type === "3cv") {
                    const [cons, vow] = currentExerciseItem.id.split("-");

                    const options = [];
                    CONSONANTS.forEach(c =>
                        VOWELS.forEach(v =>
                            options.push({type:"3cv", id:`${c}-${v.code}`})
                        )
                    );

                    const filtered = options.filter(o => o.id !== standardAssignments[idx].id);

                    newItem = filtered[Math.floor(Math.random() * filtered.length)];
                }

                // Store it
                standardAssignments[idx] = newItem;

                // Re-render just this card's DOM
                renderSingleStandardCard(card, newItem);
            }
        }
    }




</script>


</body>
</html>
