<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';
authGuard();

pageHeader('ออกแบบตู้เย็น 3D - สมาร์ท รีไซเคิล ช็อป');
?>

<!-- Three.js CDN and OrbitControls CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>

<div class="customizer-container">
    <!-- Left Column: 3D Viewport -->
    <div class="viewport-card">
        <div class="viewport-title">
            <h3 style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 1.4rem;">🧊</span> เครื่องจำลองตู้เย็นแบบ 3D
            </h3>
            <p>คลิกลากเมาส์เพื่อหมุนดูตู้เย็นรอบตัว 360° | เลื่อนลูกกลิ้งเมาส์เพื่อซูมเข้า-ออก</p>
        </div>
        <div id="canvas-container">
            <div id="canvas-loader">กำลังโหลดโมเดล 3D...</div>
        </div>
    </div>
    
    <!-- Right Column: Control Panel -->
    <div class="controls-card">
        <h2 style="font-size: 1.6rem; margin-bottom: 8px; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">🎨 ออกแบบตู้เย็นสั่งทำสีพิเศษ</h2>
        <p style="color: var(--text-secondary); margin-bottom: 24px; font-size: 0.95rem;">เลือกเฉดสีของแต่ละชิ้นส่วนตู้เย็น สลักข้อความพิเศษ และส่งจองกับทางร้านได้ทันที</p>
        
        <!-- 1. Door Color Section -->
        <div class="control-group">
            <label>1. เลือกสีบานประตู (Door Color)</label>
            <div class="color-swatches" id="door-swatches">
                <div class="swatch active" style="background-color: #cbd5e1;" data-color="#cbd5e1" data-name="เงินเมทัลลิก (Silver Metallic)"></div>
                <div class="swatch" style="background-color: #0f172a;" data-color="#0f172a" data-name="ดำสตรีทสเลท (Midnight Slate)"></div>
                <div class="swatch" style="background-color: #4f46e5;" data-color="#4f46e5" data-name="น้ำเงินอินดิโก้ (Indigo Blue)"></div>
                <div class="swatch" style="background-color: #10b981;" data-color="#10b981" data-name="เขียวมรกต (Emerald Green)"></div>
                <div class="swatch" style="background-color: #fca5a5;" data-color="#fca5a5" data-name="ชมพูสตรอว์เบอร์รี (Strawberry Pink)"></div>
                <div class="swatch" style="background-color: #fcd34d;" data-color="#fcd34d" data-name="เหลืองมัสตาร์ด (Mustard Yellow)"></div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; margin-top: 16px;">
                <span style="font-size: 0.85rem; color: var(--text-secondary);">หรือจิ้มเลือกสีตามใจชอบ:</span>
                <input type="color" id="door-color-picker" value="#cbd5e1" style="width: 48px; height: 32px; padding: 1px; margin-bottom: 0; cursor: pointer; border-radius: 4px; border: 1px solid var(--border-color);">
            </div>
            <div id="door-color-name" style="font-size: 0.85rem; color: var(--primary); margin-top: 8px; font-weight: 700;">สีที่เลือก: เงินเมทัลลิก (Silver Metallic)</div>
        </div>

        <!-- 2. Body Color Section -->
        <div class="control-group">
            <label>2. เลือกสีโครงตัวถังเครื่อง (Cabinet Color)</label>
            <div class="color-swatches" id="body-swatches">
                <div class="swatch active" style="background-color: #475569;" data-color="#475569" data-name="เทาไทเทเนียม (Titanium Slate)"></div>
                <div class="swatch" style="background-color: #1e293b;" data-color="#1e293b" data-name="ดำชาร์โคล (Charcoal Black)"></div>
                <div class="swatch" style="background-color: #f1f5f9;" data-color="#f1f5f9" data-name="ขาวมุกโพลาร์ (Polar White)"></div>
            </div>
            <div id="body-color-name" style="font-size: 0.85rem; color: var(--primary); margin-top: 8px; font-weight: 700;">สีที่เลือก: เทาไทเทเนียม (Titanium Slate)</div>
        </div>

        <!-- 3. Handle Finish Section -->
        <div class="control-group">
            <label>3. วัสดุมือจับประตู (Handle Finish)</label>
            <div class="color-swatches" id="handle-swatches">
                <div class="swatch active" style="background-color: #94a3b8;" data-color="#94a3b8" data-name="โครเมียมเงิน (Chrome Silver)"></div>
                <div class="swatch" style="background-color: #fbbf24;" data-color="#fbbf24" data-name="โครเมียมทอง (Chrome Gold)"></div>
                <div class="swatch" style="background-color: #000000;" data-color="#000000" data-name="ดำด้าน (Matte Black)"></div>
            </div>
            <div id="handle-color-name" style="font-size: 0.85rem; color: var(--primary); margin-top: 8px; font-weight: 700;">วัสดุที่เลือก: โครเมียมเงิน (Chrome Silver)</div>
        </div>

        <!-- 4. Text Engraving Section -->
        <div class="control-group">
            <label for="engraving-text">4. บริการสลักชื่อหรือข้อความบนฝาตู้เย็น (Engraving - ฟรี!)</label>
            <input type="text" id="engraving-text" maxlength="20" placeholder="พิมพ์ข้อความที่นี่ เช่น Family Home" style="margin-bottom: 8px; font-size: 0.95rem;">
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0; line-height: 1.4;">*ข้อความจะสลักนูนด้วยสีกึ่งเงาบริเวณส่วนบนบานประตูฝาตู้เย็น (สูงสุด 20 ตัวอักษร)</p>
        </div>

        <!-- 5. Pricing info -->
        <div class="price-box" style="background: rgba(79, 70, 229, 0.04); padding: 18px; border-radius: var(--radius-md); border: 1px dashed var(--primary); margin-bottom: 24px; text-align: left;">
            <div style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 500;">ราคาประเมินตู้เย็นบิวต์อินสั่งผลิตพิเศษ:</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary); margin-top: 4px;" id="estimated-price">฿16,990.00</div>
            <p style="font-size: 0.78rem; color: var(--text-muted); margin-top: 6px; margin-bottom: 0; line-height: 1.4;">(คำนวณจากราคาพื้นฐาน 15,990 บาท + ค่าสับหน้ากากและชิ้นส่วนพ่นสีพาวเดอร์โค้ท 1,000 บาท)</p>
        </div>

        <button type="button" id="btn-submit-custom" class="btn" style="width: 100%; padding: 14px 28px; font-size: 1.05rem;">
            🛒 ส่งใบสั่งจองตู้เย็นสีสั่งทำพิเศษนี้
        </button>
    </div>
</div>

<!-- Hidden Canvas used for generating text texture dynamically -->
<canvas id="text-canvas" width="512" height="128" style="display: none;"></canvas>

<!-- Three.js 3D Initialization and Interaction Logic -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('canvas-container');
    const loader = document.getElementById('canvas-loader');

    // 1. Setup Three.js Scene, Camera, and Renderer
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0xf1f5f9);

    const camera = new THREE.PerspectiveCamera(45, container.clientWidth / container.clientHeight, 0.1, 100);
    camera.position.set(0, 0.8, 7.5);

    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(container.clientWidth, container.clientHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    container.appendChild(renderer.domElement);

    // Remove loading message once initialized
    if (loader) loader.remove();

    // 2. Setup OrbitControls for rotation/zoom
    const controls = new THREE.OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.05;
    controls.minDistance = 3.5;
    controls.maxDistance = 12;
    controls.maxPolarAngle = Math.PI / 2 + 0.1; // Limit rotating below floor
    controls.target.set(0, 0, 0);

    // 3. Setup Lights
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.65);
    scene.add(ambientLight);

    const dirLight = new THREE.DirectionalLight(0xffffff, 0.8);
    dirLight.position.set(5, 8, 5);
    dirLight.castShadow = true;
    dirLight.shadow.mapSize.width = 1024;
    dirLight.shadow.mapSize.height = 1024;
    dirLight.shadow.bias = -0.001;
    scene.add(dirLight);

    const fillLight = new THREE.DirectionalLight(0x818cf8, 0.35); // Soft blue light from opposite side
    fillLight.position.set(-6, 3, -2);
    scene.add(fillLight);

    // Front soft light to highlight doors and reflections
    const frontLight = new THREE.PointLight(0xffffff, 0.45, 10);
    frontLight.position.set(0, 0.5, 3.5);
    scene.add(frontLight);

    // 4. Create Refrigerator Group & Meshes
    const fridgeGroup = new THREE.Group();
    scene.add(fridgeGroup);

    // Materials
    const bodyMaterial = new THREE.MeshStandardMaterial({
        color: 0x475569, // Charcoal slate by default
        metalness: 0.55,
        roughness: 0.28
    });

    const doorMaterial = new THREE.MeshStandardMaterial({
        color: 0xcbd5e1, // Silver Metallic by default
        metalness: 0.65,
        roughness: 0.16,
        clearcoat: 0.5,
        clearcoatRoughness: 0.1
    });

    const handleMaterial = new THREE.MeshStandardMaterial({
        color: 0x94a3b8, // Chrome silver
        metalness: 0.9,
        roughness: 0.05
    });

    const plasticPartsMaterial = new THREE.MeshStandardMaterial({
        color: 0x1e293b,
        metalness: 0.2,
        roughness: 0.8
    });

    // Cabinet Body (Back and sides)
    const cabinetGeo = new THREE.BoxGeometry(2, 4.4, 1.8);
    const cabinetMesh = new THREE.Mesh(cabinetGeo, bodyMaterial);
    cabinetMesh.position.set(0, 0, -0.1);
    cabinetMesh.castShadow = true;
    cabinetMesh.receiveShadow = true;
    fridgeGroup.add(cabinetMesh);

    // Gaskets/Dividers
    const dividerGeo = new THREE.BoxGeometry(2.02, 0.08, 0.05);
    const dividerMesh = new THREE.Mesh(dividerGeo, plasticPartsMaterial);
    dividerMesh.position.set(0, 0.42, 0.8);
    fridgeGroup.add(dividerMesh);

    // Upper Door (Freezer)
    const upperDoorGeo = new THREE.BoxGeometry(1.98, 1.34, 0.12);
    const upperDoorMesh = new THREE.Mesh(upperDoorGeo, doorMaterial);
    upperDoorMesh.position.set(0, 1.15, 0.85);
    upperDoorMesh.castShadow = true;
    fridgeGroup.add(upperDoorMesh);

    // Lower Door (Fridge)
    const lowerDoorGeo = new THREE.BoxGeometry(1.98, 2.76, 0.12);
    const lowerDoorMesh = new THREE.Mesh(lowerDoorGeo, doorMaterial);
    lowerDoorMesh.position.set(0, -0.98, 0.85);
    lowerDoorMesh.castShadow = true;
    fridgeGroup.add(lowerDoorMesh);

    // Upper Handle (Sleek minimalist bar)
    const upperHandleGroup = new THREE.Group();
    upperHandleGroup.position.set(0.8, 0.85, 0.94);
    
    const handleBarGeo = new THREE.CylinderGeometry(0.024, 0.024, 0.7, 16);
    const handleBarMesh = new THREE.Mesh(handleBarGeo, handleMaterial);
    handleBarMesh.castShadow = true;
    upperHandleGroup.add(handleBarMesh);
    
    // Connectors
    const connGeo = new THREE.BoxGeometry(0.03, 0.03, 0.06);
    const connTop = new THREE.Mesh(connGeo, handleMaterial);
    connTop.position.set(0, 0.3, -0.03);
    const connBot = connTop.clone();
    connBot.position.set(0, -0.3, -0.03);
    upperHandleGroup.add(connTop);
    upperHandleGroup.add(connBot);
    
    fridgeGroup.add(upperHandleGroup);

    // Lower Handle (Matching bar, longer)
    const lowerHandleGroup = new THREE.Group();
    lowerHandleGroup.position.set(0.8, -0.4, 0.94);
    
    const handleBarLongGeo = new THREE.CylinderGeometry(0.024, 0.024, 1.4, 16);
    const handleBarLongMesh = new THREE.Mesh(handleBarLongGeo, handleMaterial);
    handleBarLongMesh.castShadow = true;
    lowerHandleGroup.add(handleBarLongMesh);
    
    const connTopLong = connTop.clone();
    connTopLong.position.set(0, 0.65, -0.03);
    const connBotLong = connTop.clone();
    connBotLong.position.set(0, -0.65, -0.03);
    lowerHandleGroup.add(connTopLong);
    lowerHandleGroup.add(connBotLong);

    fridgeGroup.add(lowerHandleGroup);

    // Logo Emblem (Brand name plate)
    const logoPlateGeo = new THREE.BoxGeometry(0.5, 0.08, 0.02);
    const logoPlateMesh = new THREE.Mesh(logoPlateGeo, handleMaterial);
    logoPlateMesh.position.set(0, 2.05, 0.85); // Put it at the top body part
    fridgeGroup.add(logoPlateMesh);

    // Floor shadow stand
    const floorGeo = new THREE.PlaneGeometry(15, 15);
    const floorMat = new THREE.ShadowMaterial({ opacity: 0.12 });
    const floorMesh = new THREE.Mesh(floorGeo, floorMat);
    floorMesh.rotation.x = -Math.PI / 2;
    floorMesh.position.y = -2.25;
    floorMesh.receiveShadow = true;
    scene.add(floorMesh);

    // 5. Custom Dynamic Text Engraving using CanvasTexture
    const textCanvas = document.getElementById('text-canvas');
    const textCtx = textCanvas.getContext('2d');
    const textTexture = new THREE.CanvasTexture(textCanvas);
    
    const textPlateGeo = new THREE.PlaneGeometry(1.4, 0.35);
    const textPlateMat = new THREE.MeshStandardMaterial({
        map: textTexture,
        transparent: true,
        roughness: 0.1,
        metalness: 0.9,
        side: THREE.DoubleSide
    });
    
    const textPlateMesh = new THREE.Mesh(textPlateGeo, textPlateMat);
    // Put it on the Upper Freezer door
    textPlateMesh.position.set(0, 1.15, 0.92);
    fridgeGroup.add(textPlateMesh);

    function updateTextTexture(text) {
        textCtx.clearRect(0, 0, textCanvas.width, textCanvas.height);
        
        if (text.trim() !== '') {
            // Draw clean background box overlay
            textCtx.fillStyle = 'rgba(0, 0, 0, 0.0)';
            textCtx.fillRect(0, 0, textCanvas.width, textCanvas.height);
            
            // Draw Metallic Text
            textCtx.font = 'bold 38px "Plus Jakarta Sans", "Inter", sans-serif';
            textCtx.textAlign = 'center';
            textCtx.textBaseline = 'middle';
            
            // Shadow
            textCtx.fillStyle = 'rgba(0,0,0,0.3)';
            textCtx.fillText(text, textCanvas.width/2 + 2, textCanvas.height/2 + 2);
            
            // Text color (matches current handle finish)
            const handleHex = '#' + handleMaterial.color.getHexString();
            textCtx.fillStyle = handleHex === '#000000' ? '#1e293b' : (handleHex === '#fbbf24' ? '#e5c158' : '#e2e8f0');
            textCtx.fillText(text, textCanvas.width/2, textCanvas.height/2);
            
            // Text Outline for visibility
            textCtx.strokeStyle = 'rgba(0, 0, 0, 0.15)';
            textCtx.lineWidth = 1;
            textCtx.strokeText(text, textCanvas.width/2, textCanvas.height/2);
        }
        
        textTexture.needsUpdate = true;
    }

    // Initialize with empty text
    updateTextTexture('');

    // 6. Color customizer controls interactive handlers
    // Door Color Selection
    const doorSwatches = document.querySelectorAll('#door-swatches .swatch');
    const doorColorPicker = document.getElementById('door-color-picker');
    const doorColorName = document.getElementById('door-color-name');
    
    function setDoorColor(colorHex, nameText) {
        doorMaterial.color.set(colorHex);
        doorColorPicker.value = colorHex;
        doorColorName.innerText = `สีที่เลือก: ${nameText || colorHex}`;
        // Trigger render text texture to match handle color update if needed
        const engravingText = document.getElementById('engraving-text').value;
        updateTextTexture(engravingText);
    }

    doorSwatches.forEach(swatch => {
        swatch.addEventListener('click', function() {
            doorSwatches.forEach(s => s.classList.remove('active'));
            this.classList.add('active');
            setDoorColor(this.getAttribute('data-color'), this.getAttribute('data-name'));
        });
    });

    doorColorPicker.addEventListener('input', function() {
        doorSwatches.forEach(s => s.classList.remove('active'));
        setDoorColor(this.value, `สีสั่งทำพิเศษ (${this.value})`);
    });

    // Cabinet Body Color Selection
    const bodySwatches = document.querySelectorAll('#body-swatches .swatch');
    const bodyColorName = document.getElementById('body-color-name');

    bodySwatches.forEach(swatch => {
        swatch.addEventListener('click', function() {
            bodySwatches.forEach(s => s.classList.remove('active'));
            this.classList.add('active');
            bodyMaterial.color.set(this.getAttribute('data-color'));
            bodyColorName.innerText = `สีที่เลือก: ${this.getAttribute('data-name')}`;
        });
    });

    // Handle Finish Selection
    const handleSwatches = document.querySelectorAll('#handle-swatches .swatch');
    const handleColorName = document.getElementById('handle-color-name');

    handleSwatches.forEach(swatch => {
        swatch.addEventListener('click', function() {
            handleSwatches.forEach(s => s.classList.remove('active'));
            this.classList.add('active');
            const colorHex = this.getAttribute('data-color');
            handleMaterial.color.set(colorHex);
            handleColorName.innerText = `วัสดุที่เลือก: ${this.getAttribute('data-name')}`;
            
            // Re-render engraving text to match handle finish
            const engravingText = document.getElementById('engraving-text').value;
            updateTextTexture(engravingText);
        });
    });

    // Text Engraving Input
    const engravingInput = document.getElementById('engraving-text');
    engravingInput.addEventListener('input', function() {
        updateTextTexture(this.value);
    });

    // Handle Preorder submission
    const btnSubmit = document.getElementById('btn-submit-custom');
    btnSubmit.addEventListener('click', function() {
        const doorColor = '#' + doorMaterial.color.getHexString();
        const bodyColor = '#' + bodyMaterial.color.getHexString();
        const handleHex = '#' + handleMaterial.color.getHexString();
        
        let handleName = "Silver";
        if (handleHex === '#fbbf24') handleName = "Gold";
        if (handleHex === '#000000') handleName = "Black";

        const engraving = engravingInput.value.trim();
        
        // Build preorder URL redirect
        let url = `preorder.php?type=fridge_custom`;
        url += `&door_color=${encodeURIComponent(doorColor)}`;
        url += `&body_color=${encodeURIComponent(bodyColor)}`;
        url += `&handle_finish=${encodeURIComponent(handleName)}`;
        if (engraving !== '') {
            url += `&engraving=${encodeURIComponent(engraving)}`;
        }
        
        window.location.href = url;
    });

    // 7. Window resize handler
    window.addEventListener('resize', function() {
        const width = container.clientWidth;
        const height = container.clientHeight;
        
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        
        renderer.setSize(width, height);
    });

    // 8. Animation loop
    function animate() {
        requestAnimationFrame(animate);
        
        // Slowly rotate fridge when user is not dragging
        if (!controls.state === -1) {
            fridgeGroup.rotation.y += 0.0015;
        } else {
            // Keep slow idle rotation active
            fridgeGroup.rotation.y += 0.0015;
        }
        
        controls.update();
        renderer.render(scene, camera);
    }
    
    // Slight entry animation bounce
    fridgeGroup.position.y = -3;
    let clock = new THREE.Clock();
    
    // Trigger loop
    animate();
    
    // Smooth entry slide up
    const duration = 1.0;
    let elapsed = 0;
    function entryAnimation() {
        elapsed += 0.016;
        if (elapsed < duration) {
            fridgeGroup.position.y = -3 + (3 * (elapsed / duration));
            requestAnimationFrame(entryAnimation);
        } else {
            fridgeGroup.position.y = 0;
        }
    }
    entryAnimation();
});
</script>
<?php
pageFooter();
?>
