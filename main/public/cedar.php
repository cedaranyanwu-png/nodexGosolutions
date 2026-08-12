<?php
/**
 * cedar.php
 *
 * Renders CEO & Lead Architect's strategic mandate.
 * Styled with premium Tailwind CSS and modular elements.
 */

declare(strict_types=1);
require_once __DIR__ . '/../../php/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Founder mandate | nodexGosolutions</title>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;600;700&family=Orbitron:wght@600;700;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Official platform favicon references -->
    <link rel="icon" type="image/png" href="/main/assets/images/favicon.png?v=2">
</head>
<body class="bg-slate-50 text-slate-700 min-h-screen flex flex-col justify-between">

    <?php require_once __DIR__ . '/../modul/nav.html'; ?>

    <main class="container mx-auto max-w-4xl my-16 px-4">
        <div class="bg-white border border-slate-100 rounded-2xl p-8 md:p-12 shadow-sm">
            <div class="flex flex-col md:flex-row gap-8 items-center mb-8">
                <!-- SEO Optimised Profile Container: Displaying the real Cedar Anyanwu photo with descriptive alt tag and cache-busting -->
                <div class="w-24 h-24 flex-shrink-0">
                    <img
                        src="<?= htmlspecialchars((string)assetUrl('/main/assets/images/cedar-profile.jpg')) ?>"
                        alt="Cedar Anyanwu - Founder, CEO, and Systems Architect of nodexGosolutions"
                        class="rounded-full w-24 h-24 object-cover border-4 border-blue-100 shadow-sm"
                    />
                </div>
                <div>
                    <!-- Section Title detailing the Founder Name in premium typography -->
                    <h1 class="text-3xl font-extrabold text-slate-900" style="font-family: 'Orbitron', sans-serif;">Cedar Anyanwu</h1>
                    <!-- Designation sub-header styled with corporate accent blue -->
                    <p class="text-blue-600 font-semibold">Founder, CEO & Systems Architect</p>
                </div>
            </div>

            <div class="prose max-w-none text-slate-600 flex flex-col gap-6 text-sm leading-relaxed">
                <p>Cedar Anyanwu directs the corporate growth trajectory of nodexGosolutions. With over a decade of experience designing lightweight modular software frameworks, space remote sensing downlinks, and high-performance server pipelines, Cedar leads our agile engineering teams to innovate at the frontier of technology.</p>

                <p class="font-bold text-slate-900 border-l-4 border-blue-600 pl-4 italic">"African startups should not be burdened by heavy subscription overhead or complex database administration. Our zero-database architectures allow businesses to host complex computations at virtually zero cost."</p>

                <h3 class="text-lg font-bold text-slate-900 mt-4">Pioneering GIS & Space Science Adoption</h3>
                <p>Under Cedar's vision, nodexGosolutions downlinks orbital metrics and delivers soil moisture, deforestation, and land tracking data directly to African research teams. We make complex spatial telemetry accessible to local developers via streamlined API channels.</p>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../modul/footer.html'; ?>

</body>
</html>
