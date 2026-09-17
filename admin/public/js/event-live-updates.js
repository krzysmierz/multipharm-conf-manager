// Global function for showing day popup (accessible from shortcode buttons)
function showDayPopup(eventId, dayNumber, triggerButton = null) {
	if (typeof cm_event_data === "undefined") {
		console.error("cm_event_data not available");
		return;
	}

	// Get button position for animation origin
	let buttonRect = null;
	if (triggerButton) {
		buttonRect = triggerButton.getBoundingClientRect();
	}

	const modalHtml = `
        <div class="cm-day-popup-overlay fixed inset-0 bg-black bg-opacity-0 z-50 flex items-center justify-center p-4 transition-all duration-300" style="backdrop-filter: blur(0px);">
            <div class="cm-day-popup-modal bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden transform scale-0 opacity-0 transition-all duration-500 ease-out"
                 id="cm-day-popup-content"
                 style="${
										buttonRect
											? `transform-origin: ${
													buttonRect.left + buttonRect.width / 2
											  }px ${buttonRect.top + buttonRect.height / 2}px;`
											: ""
									}">
                <div class="text-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto"></div>
                    <p class="mt-2 text-gray-600 font-medium">Ładowanie...</p>
                </div>
            </div>
        </div>
    `;

	document.body.insertAdjacentHTML("beforeend", modalHtml);

	const overlay = document.querySelector(".cm-day-popup-overlay");
	const modal = document.querySelector(".cm-day-popup-modal");

	// Trigger animation
	requestAnimationFrame(() => {
		overlay.style.backgroundColor = "rgba(0, 0, 0, 0.6)";
		overlay.style.backdropFilter = "blur(4px)";
		modal.style.transform = "scale(1)";
		modal.style.opacity = "1";
	});

	const closePopup = () => {
		overlay.style.backgroundColor = "rgba(0, 0, 0, 0)";
		overlay.style.backdropFilter = "blur(0px)";
		modal.style.transform = "scale(0)";
		modal.style.opacity = "0";
		setTimeout(() => overlay.remove(), 300);
	};

	// Close on overlay click
	overlay.addEventListener("click", (e) => {
		if (e.target === overlay) closePopup();
	});

	fetch(
		`${cm_event_data.ajax_url}?action=cm_get_day_lineup&event_id=${eventId}&day_number=${dayNumber}&nonce=${cm_event_data.nonce}`
	)
		.then((response) => response.json())
		.then((data) => {
			if (data.success) {
				document.getElementById("cm-day-popup-content").innerHTML =
					data.data.html;

				// Add close button handler after content is loaded
				const closeBtn = document.querySelector(
					".cm-day-popup-overlay .cm-close-popup"
				);
				if (closeBtn) {
					closeBtn.addEventListener("click", closePopup);
				}
			} else {
				document.getElementById("cm-day-popup-content").innerHTML = `
                    <div class="p-8">
                        <p class="text-red-600">Błąd: ${
													data.data || "Nie udało się załadować danych"
												}</p>
                    </div>
                `;
			}
		})
		.catch((err) => {
			document.getElementById("cm-day-popup-content").innerHTML = `
                <div class="p-8">
                    <p class="text-red-600">Błąd połączenia z serwerem</p>
                </div>
            `;
			console.error("Day popup error:", err);
		});
}

// Make it globally available
window.showDayPopup = showDayPopup;

document.addEventListener("DOMContentLoaded", function () {
	const containers = document.querySelectorAll(".cm-live-container");

	if (containers.length === 0) return;

	// Check if cm_event_data is available
	if (typeof cm_event_data === "undefined") {
		console.warn(
			"[CM SSE] cm_event_data not available, skipping SSE initialization"
		);
		return;
	}

	const eventConnections = {};

	containers.forEach((container) => {
		const eventId = container.dataset.eventId;
		if (!eventId || eventConnections[eventId]) return;

		if (cm_event_data.debug) {
			console.log(`[CM SSE] Initializing SSE for event ${eventId}`);
		}

		const sseUrl = new URL(cm_event_data.ajax_url);
		sseUrl.searchParams.append("action", "cm_event_live_updates");
		sseUrl.searchParams.append("event_id", eventId);
		sseUrl.searchParams.append("nonce", cm_event_data.nonce); // Add nonce for security

		const eventSource = new EventSource(sseUrl.toString());
		eventConnections[eventId] = eventSource;

		// Variables for tracking day changes
		let currentActiveDay = null;
		let totalEventDays = null;

		// Event Listeners
		eventSource.addEventListener("presentation-change", function (event) {
			const presentationData = JSON.parse(event.data);
			if (cm_event_data.debug) {
				console.log(
					`[CM SSE] Presentation change for event ${eventId}:`,
					presentationData
				);
			}

			// Update total days if provided
			if (presentationData.total_days) {
				totalEventDays = parseInt(presentationData.total_days);
			}

			// Detect day change
			const newActiveDay = parseInt(presentationData.day_number);
			if (currentActiveDay !== null && currentActiveDay !== newActiveDay) {
				console.log(
					`[CM SSE] Day changed from ${currentActiveDay} to ${newActiveDay} for event ${eventId}`
				);
				if (totalEventDays) {
					updateMultiDayNavigation(eventId, newActiveDay, totalEventDays);
				}
			}
			currentActiveDay = newActiveDay;

			updateCurrentPresentation(eventId, presentationData);
		});

		eventSource.addEventListener("lineup-change", function (event) {
			const lineupData = JSON.parse(event.data);
			if (cm_event_data.debug) {
				console.log(`[CM SSE] Lineup change for event ${eventId}:`, lineupData);
			}

			// Update total days if provided
			if (lineupData.total_days) {
				totalEventDays = parseInt(lineupData.total_days);
			}

			// Update navigation buttons if total days changed
			if (lineupData.active_day && totalEventDays) {
				updateMultiDayNavigation(
					eventId,
					parseInt(lineupData.active_day),
					totalEventDays
				);
			}

			// Pass items array to updateEventLineup
			updateEventLineup(eventId, lineupData.items || lineupData);
		});

		eventSource.addEventListener("heartbeat", function (event) {
			if (cm_event_data.debug) {
				const heartbeatData = JSON.parse(event.data);
				console.log(`[CM SSE] Heartbeat for event ${eventId}:`, heartbeatData);
			}
		});

		eventSource.onopen = function () {
			console.log(`[CM SSE] Connection opened for event ${eventId}`);
		};

		eventSource.onerror = function () {
			console.error(`[CM SSE] Connection error for event ${eventId}`);
		};
	});

	// Helper functions
	function updateCurrentPresentation(eventId, presentationData) {
		const container = document.getElementById(
			`cm-current-presentation-${eventId}`
		);
		if (container) {
			container.innerHTML = renderPresentationHTML(presentationData);
		}
	}

	function updateEventLineup(eventId, lineupData) {
		const container = document.getElementById(`cm-event-lineup-${eventId}`);
		if (container) {
			// Find the lineup list element to preserve multi-day navigation
			const lineupList = container.querySelector(".cm-lineup-list");
			if (lineupList) {
				// Update only the lineup list content, keeping navigation buttons intact
				const newContent = renderLineupHTML(lineupData);
				// Extract just the inner content from the new lineup HTML
				const tempDiv = document.createElement("div");
				tempDiv.innerHTML = newContent;
				const newLineupList = tempDiv.querySelector(".cm-lineup-list");
				if (newLineupList) {
					lineupList.innerHTML = newLineupList.innerHTML;
				} else {
					lineupList.innerHTML = newContent;
				}
				console.log(
					"[CM SSE] Updated lineup list while preserving navigation buttons"
				);
			} else {
				// Fallback: replace entire content if no lineup list found
				container.innerHTML = renderLineupHTML(lineupData);
				console.log(
					"[CM SSE] Replaced entire container content (no lineup list found)"
				);
			}
		}
	}

	function updateMultiDayNavigation(eventId, currentDay, totalDays) {
		const container = document.getElementById(`cm-event-lineup-${eventId}`);
		if (!container) return;

		// Remove existing navigation buttons
		const existingNav = container.querySelector(".mt-8.text-center");
		if (existingNav) {
			existingNav.remove();
		}

		// Remove ALL existing mobile buttons for this event
		const existingMobile = document.querySelectorAll(
			`.cm-show-day-popup.md\\:hidden[data-event-id="${eventId}"]`
		);
		existingMobile.forEach((btn) => btn.remove());

		// Generate new navigation buttons
		const buttonsToShow = [];

		if (totalDays == 2) {
			// For 2-day events: show only the other day
			buttonsToShow.push(...[1, 2].filter((day) => day !== currentDay));
		} else if (totalDays > 2) {
			// For >2-day events: show neighboring days (before and after)
			if (currentDay > 1) {
				buttonsToShow.push(currentDay - 1); // Previous day
			}
			if (currentDay < totalDays) {
				buttonsToShow.push(currentDay + 1); // Next day
			}
		}

		if (buttonsToShow.length > 0) {
			const navDiv = document.createElement("div");
			navDiv.className = "mt-8 text-center";

			buttonsToShow.forEach((day) => {
				// Desktop button
				const button = document.createElement("button");
				button.className =
					"cm-show-day-popup hidden md:inline-flex items-center gap-2 mx-2 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 shadow-lg hover:shadow-xl transition-all transform hover:scale-105 font-semibold";
				button.setAttribute("data-day", day);
				button.setAttribute("data-event-id", eventId);
				button.innerHTML = `
					<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
					</svg>
					<span>Zobacz program dnia ${day}</span>
				`;

				// Add click handler
				button.addEventListener("click", function () {
					const day = this.dataset.day;
					const eventId = this.dataset.eventId;
					showDayPopup(eventId, day, this);
				});

				navDiv.appendChild(button);

				// Mobile button - fixed at bottom
				const mobileButton = document.createElement("button");
				mobileButton.className =
					"cm-show-day-popup md:hidden fixed bottom-4 right-4 w-16 h-16 bg-gradient-to-br from-indigo-600 to-purple-600 text-white rounded-2xl shadow-2xl hover:shadow-3xl hover:scale-110 transition-all z-50 flex items-center justify-center group";
				mobileButton.setAttribute("data-day", day);
				mobileButton.setAttribute("data-event-id", eventId);
				mobileButton.setAttribute("title", `Zobacz program dnia ${day}`);
				mobileButton.innerHTML = `
					<svg class="w-7 h-7 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
					</svg>
				`;

				// Add click handler
				mobileButton.addEventListener("click", function () {
					const day = this.dataset.day;
					const eventId = this.dataset.eventId;
					showDayPopup(eventId, day, this);
				});

				document.body.appendChild(mobileButton);
			});

			container.appendChild(navDiv);
			console.log(
				`[CM SSE] Updated navigation buttons for event ${eventId}, current day ${currentDay}, showing days: ${buttonsToShow.join(
					", "
				)}`
			);
		}
	}

	function renderPresentationHTML(presentation) {
		if (!presentation) {
			return '<div class="bg-gradient-to-br from-gray-50 to-gray-100 border-2 border-dashed border-gray-300 rounded-2xl p-12 text-center"><div class="text-gray-400 text-6xl mb-4">📅</div><p class="text-gray-500 text-lg font-medium">Brak aktywnej prezentacji</p></div>';
		}

		const startTimeFormatted = new Date(
			`1970-01-01T${presentation.start_time}`
		).toLocaleTimeString([], {
			hour: "2-digit",
			minute: "2-digit",
			hour12: false,
		});

		return `
            <div class="relative bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl overflow-hidden">
                <div class="absolute inset-0 bg-black opacity-10"></div>
                <div class="relative p-8 md:p-12">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center space-x-3">
                            <span class="flex h-3 w-3 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                            </span>
                            <span class="text-white text-sm font-bold uppercase tracking-widest">Trwa teraz</span>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm text-white text-sm font-semibold px-4 py-2 rounded-full border border-white/30">
                            🕒 ${startTimeFormatted}
                        </div>
                    </div>
                    <h2 class="text-3xl md:text-5xl font-bold text-white mb-4 leading-tight">${
											presentation.title
										}</h2>
                    ${
											presentation.presenter
												? `<div class="flex items-center space-x-3 mt-6">
                                <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center border-2 border-white/30">
                                    <span class="text-white text-xl">👤</span>
                                </div>
                                <div>
                                    <p class="text-white/70 text-xs uppercase tracking-wider font-semibold">Prelegent</p>
                                    <p class="text-white text-lg font-bold">${presentation.presenter}</p>
                                </div>
                            </div>`
												: ""
										}
                </div>
            </div>
        `;
	}

	function renderLineupHTML(lineup) {
		if (!lineup) {
			return '<div class="bg-gradient-to-br from-gray-50 to-gray-100 border-2 border-dashed border-gray-300 rounded-2xl p-12 text-center"><div class="text-gray-400 text-6xl mb-4">📋</div><p class="text-gray-500 text-lg font-medium">Brak zaplanowanych prezentacji</p></div>';
		}

		// Convert object to array if needed
		const lineupArray = Array.isArray(lineup) ? lineup : Object.values(lineup);

		if (lineupArray.length === 0) {
			return '<div class="bg-gradient-to-br from-gray-50 to-gray-100 border-2 border-dashed border-gray-300 rounded-2xl p-12 text-center"><div class="text-gray-400 text-6xl mb-4">📋</div><p class="text-gray-500 text-lg font-medium">Brak zaplanowanych prezentacji</p></div>';
		}

		// Sort lineup chronologically by start_time
		lineupArray.sort((a, b) => {
			if (!a.start_time || !b.start_time) return 0;
			return a.start_time.localeCompare(b.start_time);
		});

		const currentTime = new Date().toTimeString().slice(0, 8);

		const itemsHtml = lineupArray
			.map((item) => {
				// Calculate end_time if not available
				let endTime = item.end_time;
				if (!endTime && item.start_time && item.duration_minutes) {
					const startTime = new Date(`1970-01-01T${item.start_time}`);
					const endTimeMs =
						startTime.getTime() + item.duration_minutes * 60 * 1000;
					const endTimeDate = new Date(endTimeMs);
					endTime = endTimeDate.toTimeString().slice(0, 8);
				}

				const isPast = endTime && endTime < currentTime && !item.is_active;
				const isCurrent = item.is_active == "1" || item.is_active === true;

				// Debug log
				if (typeof cm_event_data !== "undefined" && cm_event_data.debug) {
					console.log(
						`Item ${item.title}: is_active=${item.is_active}, isCurrent=${isCurrent}, isPast=${isPast}`
					);
				}

				const startTimeFormatted = item.start_time
					? new Date(`1970-01-01T${item.start_time}`).toLocaleTimeString([], {
							hour: "2-digit",
							minute: "2-digit",
							hour12: false,
					  })
					: "";

				// Calculate end time formatted
				let endTimeFormatted = "";
				if (item.start_time && item.duration_minutes) {
					const startTime = new Date(`1970-01-01T${item.start_time}`);
					const endTimeMs =
						startTime.getTime() + item.duration_minutes * 60 * 1000;
					const endTimeDate = new Date(endTimeMs);
					endTimeFormatted = endTimeDate.toLocaleTimeString([], {
						hour: "2-digit",
						minute: "2-digit",
						hour12: false,
					});
				}

				if (isCurrent) {
					return `
						<div class="cm-lineup-item relative bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl shadow-xl p-6 mb-6 overflow-hidden transform transition-all hover:scale-[1.02] border-2 border-blue-400">
							<div class="absolute inset-0 bg-white opacity-5"></div>
							<span class="absolute top-4 right-4 flex h-3 w-3">
								<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
								<span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
							</span>
							<div class="relative flex items-start gap-6">
								<div class="flex-shrink-0">
									<div class="bg-white/20 backdrop-blur-sm rounded-xl px-4 py-3 border-2 border-white/30 min-w-[80px] text-center">
										<div class="text-white text-2xl font-bold leading-none">${startTimeFormatted}</div>
										${
											endTimeFormatted
												? `<div class="text-white/70 text-xs mt-1">do ${endTimeFormatted}</div>`
												: ""
										}
										${
											item.duration_minutes && !endTimeFormatted
												? `<div class="text-white/70 text-xs mt-1">${item.duration_minutes} min</div>`
												: ""
										}
									</div>
								</div>
								<div class="flex-grow min-w-0">
									<div class="mb-3">
										<h5 class="text-white text-xl md:text-2xl font-bold mb-2 leading-tight">${
											item.title
										}</h5>
										${
											item.presenter
												? `<div class="flex items-center gap-2 text-white/90">
													<span class="text-lg">👤</span>
													<span class="font-semibold">${item.presenter}</span>
												</div>`
												: ""
										}
									</div>
									${
										item.description
											? `<p class="text-white/80 text-sm md:text-base leading-relaxed mt-3">${item.description}</p>`
											: ""
									}

								</div>
							</div>
						</div>
					`;
				} else if (isPast) {
					return `
						<div class="cm-lineup-item past bg-white rounded-xl shadow-sm border-2 border-gray-200 p-5 mb-4 opacity-50 hover:opacity-75 transition-opacity">
							<div class="flex items-start gap-4">
								<div class="flex-shrink-0">
									<div class="bg-gray-100 rounded-lg px-3 py-2 min-w-[70px] text-center">
										<div class="text-gray-500 text-lg font-semibold">${startTimeFormatted}</div>
									</div>
								</div>
								<div class="flex-grow min-w-0">
									<h5 class="text-gray-600 text-lg font-semibold mb-1 line-through">${
										item.title
									}</h5>
									${
										item.presenter
											? `<p class="text-gray-500 text-sm">👤 ${item.presenter}</p>`
											: ""
									}
									${
										item.description
											? `<p class="text-gray-400 text-sm mt-2 line-clamp-2">${item.description}</p>`
											: ""
									}
								</div>
							</div>
						</div>
					`;
				} else {
					return `
						<div class="cm-lineup-item bg-white rounded-xl shadow-md hover:shadow-lg border-2 border-gray-200 hover:border-indigo-300 p-5 mb-4 transition-all">
							<div class="flex items-stretch w-full gap-5">
								<div class="flex-shrink-0">
									<div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-lg px-4 py-3 min-w-[80px] text-center border border-indigo-200">
										<div class="text-indigo-700 text-xl font-bold">${startTimeFormatted}</div>
									</div>
								</div>
								<div class="flex-grow min-w-0">
									<h5 class="text-gray-900 text-xl text-left font-bold mb-2 leading-tight">${
										item.title
									}</h5>
									${
										item.presenter
											? `<div class="flex items-center gap-2 text-gray-700 mb-2">
												<span class="text-base">👤</span>
												<span class="font-medium">${item.presenter}</span>
											</div>`
											: ""
									}
									${
										item.description
											? `<p class="text-gray-600 text-sm leading-relaxed mt-2">${item.description}</p>`
											: ""
									}
								</div>
							</div>
						</div>
					`;
				}
			})
			.join("");

		return `<div class="cm-lineup-list space-y-4">${itemsHtml}</div>`;
	}
});
