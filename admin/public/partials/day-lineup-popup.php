<div class="cm-day-popup-content">
    <!-- Header with gradient background -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-700 text-white px-8 py-6 rounded-t-2xl relative overflow-hidden">
        <div class="absolute inset-0 bg-black opacity-10"></div>
        <div class="relative flex justify-between items-center">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="bg-white/20 backdrop-blur-sm text-white text-xs font-bold uppercase px-3 py-1 rounded-full border border-white/30">
                        Dzień <?php echo $day_number; ?>
                    </span>
                </div>
                <h3 class="text-3xl font-bold mb-2 leading-tight">Program Dnia</h3>
                <p class="text-white/90 text-sm font-medium flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <?php
                    if (!empty($day_date)) {
                        try {
                            $day_date_obj = new DateTime($day_date);
                            $day_names = ['Niedziela', 'Poniedziałek', 'Wtorek', 'Środa', 'Czwartek', 'Piątek', 'Sobota'];
                            echo $day_names[$day_date_obj->format('w')] . ', ' . $day_date_obj->format('d.m.Y');
                        } catch (Exception $e) {
                            echo 'Dzień ' . esc_html($day_number);
                        }
                    } else {
                        echo 'Dzień ' . esc_html($day_number);
                    }
                    ?>
                </p>
            </div>
            <button class="cm-close-popup text-white hover:bg-white/20 transition-all p-2 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Content with lineup items -->
    <div class="p-8 max-h-[65vh] overflow-y-auto bg-gradient-to-br from-gray-50 to-white">
        <?php if (empty($lineup_items)): ?>
            <div class="text-center py-12">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <p class="text-gray-500 text-lg">Brak zaplanowanych prezentacji na ten dzień</p>
            </div>
        <?php else: ?> 
            <div class="space-y-3">
                <?php
                // Sort by start_time
                usort($lineup_items, function($a, $b) {
                    return strcmp($a->start_time, $b->start_time);
                });

                $current_time = current_time('H:i:s');

                foreach ($lineup_items as $item):
                    // Calculate if item is current or past
                    $end_time = null;
                    if ($item->duration_minutes) {
                        $end_time_obj = new DateTime($item->start_time);
                        $end_time_obj->modify("+{$item->duration_minutes} minutes");
                        $end_time = $end_time_obj->format('H:i:s');
                    }

                    $is_current = $item->is_active == '1';
                    $is_past = !$is_current && $end_time && $end_time < $current_time;

                    // Build item HTML based on state
                    if ($is_current) {
                        // Active presentation - gradient style
                        ?>
                        <div class="cm-lineup-item relative bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl shadow-lg p-5 overflow-hidden">
                            <div class="absolute inset-0 bg-white opacity-5"></div>
                            <span class="absolute top-3 right-3 flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
                            </span>
                            <div class="relative flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <div class="bg-white/20 backdrop-blur-sm rounded-lg px-3 py-2 border border-white/30 min-w-[65px] text-center">
                                        <div class="text-white text-xl font-bold leading-none">
                                            <?php echo esc_html(date('H:i', strtotime($item->start_time))); ?>
                                        </div>
                                        <?php if ($item->duration_minutes): ?>
                                            <div class="text-white/70 text-xs mt-1">
                                                <?php echo esc_html($item->duration_minutes); ?> min
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex-grow min-w-0">
                                    <h4 class="font-bold text-xl text-white mb-1.5 leading-tight"><?php echo esc_html($item->title); ?></h4>
                                    <?php if ($item->presenter): ?>
                                        <p class="text-white/90 text-sm mb-2 flex items-center gap-2">
                                            <span>👤</span>
                                            <span class="font-medium"><?php echo esc_html($item->presenter); ?></span>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($item->description): ?>
                                        <p class="text-white/80 text-sm leading-relaxed"><?php echo esc_html($item->description); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php } elseif ($is_past) { ?>
                        <div class="cm-lineup-item bg-white rounded-xl shadow-sm border border-gray-200 p-4 opacity-50 hover:opacity-75 transition-opacity">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <div class="bg-gray-100 rounded-lg px-3 py-2 min-w-[65px] text-center">
                                        <div class="text-gray-500 text-lg font-semibold">
                                            <?php echo esc_html(date('H:i', strtotime($item->start_time))); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-grow min-w-0">
                                    <h4 class="font-semibold text-lg text-gray-600 mb-1 line-through"><?php echo esc_html($item->title); ?></h4>
                                    <?php if ($item->presenter): ?>
                                        <p class="text-gray-500 text-sm">👤 <?php echo esc_html($item->presenter); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="cm-lineup-item bg-white rounded-xl shadow-md hover:shadow-lg border border-gray-200 hover:border-indigo-300 p-5 transition-all">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-lg px-3 py-2 min-w-[65px] text-center border border-indigo-200">
                                        <div class="text-indigo-700 text-xl font-bold">
                                            <?php echo esc_html(date('H:i', strtotime($item->start_time))); ?>
                                        </div>
                                        <?php if ($item->duration_minutes): ?>
                                            <div class="text-indigo-600 text-xs mt-1">
                                                <?php echo esc_html($item->duration_minutes); ?> min
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex-grow min-w-0">
                                    <h4 class="font-bold text-xl text-gray-900 mb-1.5 leading-tight"><?php echo esc_html($item->title); ?></h4>
                                    <?php if ($item->presenter): ?>
                                        <p class="text-gray-700 text-sm mb-2 flex items-center gap-2">
                                            <span>👤</span>
                                            <span class="font-medium"><?php echo esc_html($item->presenter); ?></span>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($item->description): ?>
                                        <p class="text-gray-600 text-sm leading-relaxed"><?php echo esc_html($item->description); ?></p>
                                    <?php endif; ?>
                                    <?php if ($item->event_type && $item->event_type !== 'talk'): ?>
                                        <span class="inline-block mt-2 text-xs bg-indigo-100 text-indigo-700 px-2.5 py-1 rounded-full font-medium">
                                            <?php
                                            $types = [
                                                'workshop' => 'Warsztat',
                                                'panel' => 'Panel dyskusyjny',
                                                'break' => 'Przerwa',
                                                'networking' => 'Networking'
                                            ];
                                            echo isset($types[$item->event_type]) ? $types[$item->event_type] : ucfirst($item->event_type);
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>