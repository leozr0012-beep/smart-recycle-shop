<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

pageHeader('ที่ตั้งร้านและข้อมูลติดต่อ');
?>

<div class="guest-container" style="max-width: 1100px; margin: 40px auto; text-align: left;">
    <h2 style="font-size: 2rem; margin-bottom: 12px; text-align: center; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
        ที่ตั้งคลังสินค้าหลักและช่องทางการติดต่อ
    </h2>
    <p style="text-align: center; color: var(--text-secondary); margin-bottom: 40px; font-size: 1.1rem;">
        คุณสามารถเดินทางเข้ามาเยี่ยมชมสินค้าจริง ทดสอบการทำงาน หรือสอบถามข้อมูลเพิ่มเติมกับช่างเทคนิคได้โดยตรง
    </p>

    <div class="grid-2" style="gap: 32px; align-items: start;">
        <!-- Column 1: Map & Address Details -->
        <div class="card" style="margin-bottom: 0;">
            <h3 style="font-size: 1.4rem; margin-bottom: 20px; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width: 24px; height: 24px; fill: var(--primary);">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
                ตำแหน่งร้านค้าบนแผนที่
            </h3>
            
            <!-- Google Maps Embed (Phatthanakan, Bangkok) -->
            <div style="background: #f1f5f9; border-radius: 12px; overflow: hidden; margin-bottom: 24px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color);">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3875.9221817441584!2d100.618641!3d13.738318!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x311d60069e2c6f13%3A0x89791bc5de30b957!2z4LiW4LiZ4LiZ4Lie4Lix4LiZ4LiB4Liy4Lij!5e0!3m2!1sth!2sth!4v1700000000000!5m2!1sth!2sth" width="100%" height="360" style="border:0; display: block;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>

            <div style="line-height: 1.8;">
                <p style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">📍 สมาร์ท รีไซเคิล ช็อป (คลังสินค้าพัฒนาการ)</p>
                <p style="color: var(--text-secondary); margin-bottom: 12px;">123/45 ถนนพัฒนาการ แขวงสวนหลวง เขตสวนหลวง กรุงเทพมหานคร 10250</p>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                    <div>
                        <p style="font-weight: 600; color: var(--text-primary); font-size: 0.9rem;">📞 สายด่วนโทรศัพท์</p>
                        <p style="font-size: 1.1rem; color: var(--primary); font-weight: 700;">090-000-0000</p>
                    </div>
                    <div>
                        <p style="font-weight: 600; color: var(--text-primary); font-size: 0.9rem;">💬 LINE Official Account</p>
                        <p style="font-size: 1.1rem; color: #22c55e; font-weight: 700;">@smartrecycle</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Column 2: Directions & Shipping Calculator -->
        <div class="card" style="margin-bottom: 0;">
            <h3 style="font-size: 1.4rem; margin-bottom: 20px; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width: 24px; height: 24px; fill: var(--primary);">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                </svg>
                คู่มือการเดินทาง & คำนวณส่งฟรี
            </h3>
            
            <div style="background: rgba(79, 70, 229, 0.03); border-radius: 12px; padding: 20px; border: 1px solid rgba(79, 70, 229, 0.1); margin-bottom: 24px;">
                <h4 style="margin-bottom: 10px; color: var(--primary);">🚗 วิธีการเดินทางมาร้าน</h4>
                <ul style="padding-left: 20px; color: var(--text-secondary); font-size: 0.95rem; line-height: 1.7;">
                    <li style="margin-bottom: 8px;">
                        <strong>โดยรถยนต์ส่วนตัว:</strong> วิ่งบนเส้นถนนพัฒนาการ มุ่งหน้าสุขุมวิท 77 ร้านจะอยู่เยื้องตรงข้ามกับปั๊มน้ำมันหลัก มีที่จอดรถรับรองหน้าคลังสินค้าอย่างกว้างขวาง
                    </li>
                    <li style="margin-bottom: 8px;">
                        <strong>โดยรถไฟฟ้า (Airport Rail Link):</strong> ลงสถานี <strong>หัวหมาก (Hua Mak)</strong> จากนั้นนั่งรถประจำทางหรือแท็กซี่ต่อมาตามถนนพัฒนาการระยะทางประมาณ 2 กม.
                    </li>
                    <li style="margin-bottom: 0;">
                        <strong>จุดสังเกต:</strong> ป้ายหน้าร้านขนาดใหญ่ "สมาร์ท รีไซเคิล ช็อป" สีเขียว-ม่วงสะท้อนแสง สังเกตเห็นได้ง่าย
                    </li>
                </ul>
            </div>

            <!-- Shipping Calculator Form -->
            <div style="background: rgba(16, 185, 129, 0.03); border-radius: 12px; padding: 20px; border: 1px solid rgba(16, 185, 129, 0.1);">
                <h4 style="margin-bottom: 10px; color: var(--secondary);">🚛 เครื่องคำนวณค่าจัดส่งสินค้า</h4>
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 15px;">
                    คลังสินค้าของเราให้บริการจัดส่งฟรีในระยะ <strong><?= FREE_SHIPPING_KM ?> กม.</strong> หากเกินจะคิดราคาตามระยะทางจริง
                </p>

                <form id="shipping-calc-form" onsubmit="event.preventDefault();" style="margin-bottom: 0;">
                    <label for="distance_input" style="font-size: 0.85rem; color: var(--text-secondary);">ระยะห่างจากร้านค้าถึงบ้านคุณ (กิโลเมตร)</label>
                    <div style="display: flex; gap: 12px; margin-bottom: 0;">
                        <input type="number" id="distance_input" name="distance" min="0" step="0.1" placeholder="เช่น 5.5 หรือ 22.0" required style="margin-bottom: 0; flex: 1;">
                        <button type="button" onclick="calculateDistanceShipping()" class="btn btn-secondary" style="padding: 10px 24px; white-space: nowrap;">คำนวณราคา</button>
                    </div>
                </form>

                <div id="calc-result" class="status-card" style="margin-top: 16px; display: none; padding: 16px; margin-bottom: 0; box-shadow: none;">
                    <!-- Results populated dynamically by JS -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function calculateDistanceShipping() {
    const distanceInput = document.getElementById('distance_input');
    const calcResult = document.getElementById('calc-result');
    const distance = parseFloat(distanceInput.value);
    
    if (isNaN(distance) || distance < 0) {
        calcResult.style.display = 'block';
        calcResult.style.borderLeft = '4px solid #ef4444';
        calcResult.className = 'status-card';
        calcResult.innerHTML = '<p style="color:#ef4444; font-weight:600; margin:0;">❌ กรุณากรอกระยะทางที่เป็นตัวเลขและมีค่ามากกว่า 0</p>';
        return;
    }

    const freeLimit = <?= FREE_SHIPPING_KM ?>;
    let cost = 0;
    if (distance > freeLimit) {
        cost = 100 + Math.max(0, distance - freeLimit) * 10;
    }

    calcResult.style.display = 'block';
    calcResult.style.borderLeft = '4px solid ' + (distance <= freeLimit ? '#10b981' : '#4f46e5');
    calcResult.className = 'status-card';

    let resultHTML = `<h4 style="margin-bottom:8px; color:var(--text-primary);">ผลการคำนวณ</h4>`;
    resultHTML += `<p style="margin:4px 0; font-size:0.95rem;">ระยะทางจัดส่ง: <strong>${distance.toFixed(1)} กิโลเมตร</strong></p>`;
    
    if (distance <= freeLimit) {
        resultHTML += `<p style="margin:8px 0 0 0; color:#10b981; font-weight:700; font-size:1.1rem;">🎉 บริการจัดส่งฟรี! (เนื่องจากอยู่ในระยะ ${freeLimit} กม.)</p>`;
    } else {
        resultHTML += `<p style="margin:8px 0 4px 0; font-size:1rem;">อัตราค่าจัดส่ง: <strong style="color:var(--primary); font-size:1.2rem;">฿${cost.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></p>`;
        resultHTML += `<p style="font-size:0.8rem; color:var(--text-muted); margin-top:8px; margin-bottom:0; line-height:1.4;">* เงื่อนไข: จัดส่งฟรี 15 กิโลเมตรแรก ส่วนที่เกินคิดกิโลเมตรละ 10 บาท บวกด้วยค่าบริการพื้นฐาน 100 บาท</p>`;
    }
    
    calcResult.innerHTML = resultHTML;
}
</script>

<?php
pageFooter();
?>
