<?php
// C:\xampp_new\htdocs\BloodConnect\App\Shared\Presentation\View\home.php
?>

<!-- FontAwesome & Google Fonts for beautiful text weights -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- ============ REGULAR MODERN CLASSIC INTERIOR VIEW CONTENT ============ -->
<section class="relative overflow-hidden py-10 lg:py-16 bg-gray-50">

    <!-- Animated Trendy Background Geometric Blobs -->
    <div class="absolute right-0 top-0 w-full max-w-3xl pointer-events-none opacity-30 lg:opacity-90 z-0 select-none animate-pulse duration-[8000ms]">
        <svg viewBox="0 0 700 500" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto block">
            <path d="M700,100 C500,-20 300,200 0,400 L700,500 Z" fill="#FCEAEF" />
        </svg>
    </div>
    <div class="absolute left-10 bottom-10 w-72 h-72 bg-red-50/40 rounded-full filter blur-3xl pointer-events-none z-0"></div>

    <!-- Main Outer Wrapper -->
    <div class="relative z-10 max-w-[1440px] mx-auto w-full px-6 md:px-12">

        <!-- HERO SEGMENT GRID -->
        <div class="relative overflow-hidden rounded-[2rem] bg-white border border-red-50 shadow-xl shadow-red-100/40 mb-16">

            <!-- Light Gradient Blobs -->
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-gradient-to-br from-red-100 to-rose-50 rounded-full blur-3xl pointer-events-none opacity-70"></div>
            <div class="absolute -bottom-28 -left-20 w-80 h-80 bg-red-50 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-10 items-center px-6 md:px-12 py-12 lg:py-16">

                <!-- Left Grid: Copy -->
                <div class="lg:col-span-7 flex flex-col items-center lg:items-start text-center lg:text-left space-y-6">
                    <span class="inline-flex items-center gap-2 bg-red-50 text-red-600 border border-red-100 px-4 py-1.5 rounded-full text-sm font-bold tracking-wide uppercase shadow-sm">
                        <i class="fa-solid fa-code"></i> BloodConnect API
                    </span>

                    <h1 class="font-black text-4xl md:text-6xl text-gray-900 tracking-tight leading-tight">
                        Boost Your<br>
                        <span class="text-red-600">Life-Saving</span> Impact.
                    </h1>

                    <p class="text-base md:text-xl text-gray-500 font-medium max-w-xl leading-relaxed">
                        BloodConnect is a secure and reliable platform that connects blood donors with patients, making it easier to find life-saving blood when it is needed most.
                    </p>

                </div>

                <!-- Right Grid: Code Editor Mockup -->
                <div class="lg:col-span-5 relative flex justify-center w-full">
                    <div class="w-full max-w-md bg-white border border-gray-200 rounded-2xl shadow-2xl shadow-red-100/60 overflow-hidden">
                        <!-- Window Chrome -->
                        <div class="flex items-center gap-2 bg-gray-100 border-b border-gray-200 px-4 py-3">
                            <span class="w-3 h-3 rounded-full bg-red-400"></span>
                            <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                            <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                            <span class="ml-3 text-xs font-semibold text-gray-500 font-mono">BloodRequestController.php</span>
                        </div>
                        <!-- Code Body -->
                        <div class="bg-gray-50 p-5 font-mono text-[13px] leading-[1.9] text-left overflow-x-auto">
                            <pre class="m-0 whitespace-pre text-gray-800"><span class="bc-line" style="animation-delay:.3s"><span class="text-purple-600">class</span> <span class="text-red-600">BloodRequestController</span> {
</span><span class="bc-line" style="animation-delay:.9s">    <span class="text-purple-600">public function</span> matchDonor() {
</span><span class="bc-line" style="animation-delay:1.5s">        $donor = <span class="text-sky-600">$this-&gt;findNearest</span>(
</span><span class="bc-line" style="animation-delay:2.1s">            blood: <span class="text-emerald-700">'O+'</span>,
</span><span class="bc-line" style="animation-delay:2.7s">            radius: <span class="text-sky-700">'5km'</span>
</span><span class="bc-line" style="animation-delay:3.3s">        );
</span><span class="bc-line" style="animation-delay:3.9s">        <span class="text-gray-400">// Donor found!</span>
</span><span class="bc-line" style="animation-delay:4.5s">        <span class="text-purple-600">return</span> $donor;  <span class="text-gray-400">// ~12 min</span>
</span><span class="bc-line" style="animation-delay:5.1s">    }
</span><span class="bc-line" style="animation-delay:5.7s">}<span class="bc-cursor">|</span></span></pre>
                        </div>
                    </div>

                    <!-- Floating Donation Counter Badge -->
                    <div class="bc-badge-glow absolute -top-4 -right-3 md:-right-5 bg-white rounded-full px-4 py-2 shadow-lg border border-red-100 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-xs font-black text-gray-800"><span class="counter-value" data-target="<?= $successful_donations ?>">0</span>+ Donations</span>
                    </div>
                </div>

            </div>

            <!-- Blood Type Marquee -->
            <div class="relative border-t border-gray-100 bg-white/60 py-3.5 overflow-hidden">
                <div class="flex w-max whitespace-nowrap" style="animation: bc-marquee 24s linear infinite;">
                    <div class="flex items-center gap-10 pr-10 text-sm font-black text-red-600 tracking-widest uppercase">
                        <span>A+</span><span>B+</span><span>AB+</span><span>O+</span><span>A-</span><span>B-</span><span>AB-</span><span>O-</span>
                    </div>
                    <div class="flex items-center gap-10 pr-10 text-sm font-black text-red-600 tracking-widest uppercase" aria-hidden="true">
                        <span>A+</span><span>B+</span><span>AB+</span><span>O+</span><span>A-</span><span>B-</span><span>AB-</span><span>O-</span>
                    </div>
                </div>
            </div>
        </div>

        <style>
            @keyframes bc-marquee {
                0% { transform: translateX(0); }
                100% { transform: translateX(-50%); }
            }
            .bc-line {
                display: inline;
                opacity: 0;
                animation: bc-line-in .5s ease-out forwards;
            }
            @keyframes bc-line-in {
                from { opacity: 0; transform: translateY(6px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .bc-cursor {
                display: inline-block;
                color: #dc2626;
                font-weight: bold;
                animation: bc-blink 1s steps(2) infinite;
            }
            @keyframes bc-blink {
                0%, 50% { opacity: 1; }
                51%, 100% { opacity: 0; }
            }
            .bc-badge-glow {
                animation: bc-glow 2.2s ease-in-out infinite;
            }
            @keyframes bc-glow {
                0%, 100% {
                    box-shadow: 0 4px 10px rgba(220, 38, 38, 0.18);
                    transform: scale(1);
                }
                50% {
                    box-shadow: 0 4px 22px rgba(220, 38, 38, 0.55);
                    transform: scale(1.06);
                }
            }
        </style>

        <!-- STATISTICS COUNTER ROW -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-16 relative z-10">
            <div class="group bg-white border border-gray-200/60 rounded-2xl p-6 text-center shadow-sm hover:shadow-lg hover:border-red-200 hover:bg-red-50/30 transition-all duration-300 transform hover:-translate-y-1">
                <div class="w-10 h-10 mx-auto mb-3 rounded-xl bg-red-50 text-red-600 flex items-center justify-center group-hover:bg-red-600 group-hover:text-white transition-colors duration-300">
                    <i class="fa-solid fa-droplet text-lg"></i>
                </div>
                <span class="text-3xl md:text-4xl font-black text-red-600"><span class="counter-value" data-target="<?= $successful_donations ?>">0</span></span>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mt-1">Successful Donations</p>
            </div>
            <div class="group bg-white border border-gray-200/60 rounded-2xl p-6 text-center shadow-sm hover:shadow-lg hover:border-red-200 hover:bg-red-50/30 transition-all duration-300 transform hover:-translate-y-1">
                <div class="w-10 h-10 mx-auto mb-3 rounded-xl bg-red-50 text-red-600 flex items-center justify-center group-hover:bg-red-600 group-hover:text-white transition-colors duration-300">
                    <i class="fa-solid fa-hand-holding-heart text-lg"></i>
                </div>
                <span class="text-3xl md:text-4xl font-black text-red-600"><span class="counter-value" data-target="<?= $total_donors ?>">0</span></span>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mt-1">Active Donors</p>
            </div>
            <div class="group bg-white border border-gray-200/60 rounded-2xl p-6 text-center shadow-sm hover:shadow-lg hover:border-red-200 hover:bg-red-50/30 transition-all duration-300 transform hover:-translate-y-1">
                <div class="w-10 h-10 mx-auto mb-3 rounded-xl bg-red-50 text-red-600 flex items-center justify-center group-hover:bg-red-600 group-hover:text-white transition-colors duration-300">
                    <i class="fa-solid fa-users text-lg"></i>
                </div>
                <span class="text-3xl md:text-4xl font-black text-red-600"><span class="counter-value" data-target="<?= $total_users ?>">0</span></span>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mt-1">Total Users</p>
            </div>
            <div class="group bg-white border border-gray-200/60 rounded-2xl p-6 text-center shadow-sm hover:shadow-lg hover:border-red-200 hover:bg-red-50/30 transition-all duration-300 transform hover:-translate-y-1">
                <div class="w-10 h-10 mx-auto mb-3 rounded-xl bg-red-50 text-red-600 flex items-center justify-center group-hover:bg-red-600 group-hover:text-white transition-colors duration-300">
                    <i class="fa-solid fa-truck-medical text-lg"></i>
                </div>
                <span class="text-3xl md:text-4xl font-black text-red-600">24/7</span>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mt-1">Emergency Support</p>
            </div>
        </div>

        <!-- QUICK ACTION CARD MODULES -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-20 relative z-10">

            <!-- Card 1 -->
            <div class="group bg-white border border-gray-200/80 rounded-2xl p-8 shadow-sm hover:shadow-xl hover:border-red-200 transition-all duration-300 transform hover:-translate-y-1">
                <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center mb-6 group-hover:bg-red-600 group-hover:text-white transition-colors duration-300">
                    <i class="fa-solid fa-magnifying-glass text-xl"></i>
                </div>
                <h3 class="font-bold text-xl text-gray-900 mb-2">Search Donors</h3>
                <p class="text-gray-500 font-medium text-base leading-relaxed">
                    Filter verified active blood donors instantly within your geographical radius zone parameters.
                </p>
            </div>

            <!-- Card 2 -->
            <div class="group bg-white border border-gray-200/80 rounded-2xl p-8 shadow-sm hover:shadow-xl hover:border-red-200 transition-all duration-300 transform hover:-translate-y-1">
                <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center mb-6 group-hover:bg-red-600 group-hover:text-white transition-colors duration-300">
                    <i class="fa-solid fa-bullhorn text-xl"></i>
                </div>
                <h3 class="font-bold text-xl text-gray-900 mb-2">Post Emergency Request</h3>
                <p class="text-gray-500 font-medium text-base leading-relaxed">
                    Broadcast a tracking request to all matching groups across our database for swift turnaround support.
                </p>
            </div>

            <!-- Card 3 -->
            <div class="group bg-white border border-gray-200/80 rounded-2xl p-8 shadow-sm hover:shadow-xl hover:border-red-200 transition-all duration-300 transform hover:-translate-y-1">
                <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center mb-6 group-hover:bg-red-600 group-hover:text-white transition-colors duration-300">
                    <i class="fa-solid fa-shield-halved text-xl"></i>
                </div>
                <h3 class="font-bold text-xl text-gray-900 mb-2">Secure Routing</h3>
                <p class="text-gray-500 font-medium text-base leading-relaxed">
                    Your direct contact metrics are safely stored and encrypted, keeping communication secure at all points.
                </p>
            </div>

        </div>

    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const counters = document.querySelectorAll('.counter-value');
        const speed = 40;

        const animate = (counter) => {
            const target = parseInt(counter.getAttribute('data-target'));
            const increment = Math.max(1, Math.floor(target / 30));
            let current = 0;

            const update = () => {
                current += increment;
                if (current >= target) {
                    counter.textContent = target.toLocaleString();
                    return;
                }
                counter.textContent = current.toLocaleString();
                requestAnimationFrame(() => setTimeout(update, speed));
            };

            update();
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animate(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.5
        });

        counters.forEach(c => observer.observe(c));
    });
</script>