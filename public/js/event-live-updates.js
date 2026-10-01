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

	containers.forEach((container) => {
		synchronizeRaffleDrawLayout(container.dataset.eventId);
		synchronizeRaffleSidebar(container.dataset.eventId);
	});

	// Helper functions
	function updateCurrentPresentation(eventId, presentationData) {
		// The draw component owns a running animation. Keep its live DOM node in
		// place until that animation settles instead of replacing it with SSE HTML.
		if (isRaffleDrawing(eventId)) {
			window.setTimeout(function () {
				updateCurrentPresentation(eventId, presentationData);
			}, 250);
			return;
		}

		const containers = document.querySelectorAll(
			`[id="cm-current-presentation-${eventId}"]`
		);
		containers.forEach((container) => {
			container.innerHTML = presentationData && presentationData.rendered_html
				? presentationData.rendered_html
				: renderPresentationHTML(presentationData);
		});
		synchronizeRaffleDrawLayout(eventId, presentationData && presentationData.rendered_html, true);
		synchronizeRaffleSidebar(eventId);
	}

	function updateEventLineup(eventId, lineupData) {
		const containers = document.querySelectorAll(`[id="cm-event-lineup-${eventId}"]`);
		containers.forEach((container) => {
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
					lineupList.className = newLineupList.className;
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
			updateUpcomingLineupItem(container, lineupData);
		});
		synchronizeRaffleSidebar(eventId);
	}

	function updateUpcomingLineupItem(container, lineupData) {
		const panel = container.querySelector(".cm-lineup-upcoming");
		if (!panel) return;

		const nextItem = getUpcomingLineupItem(lineupData);
		panel.innerHTML = renderUpcomingLineupHTML(nextItem);
		panel.hidden = !nextItem;
		updateAsideVisibility(container);
	}

	function updateAsideVisibility(container) {
		if (!container) return;
		const aside = container.querySelector(".cm-event-lineup-layout__aside");
		if (!aside) return;
		aside.hidden = !aside.querySelector(".cm-lineup-raffle-draw:not([hidden]), .cm-lineup-raffle:not([hidden]), .cm-lineup-upcoming:not([hidden])");
		const grid = aside.closest(".cm-event-lineup-layout__grid");
		if (grid) grid.classList.toggle("cm-event-lineup-layout__grid--single", aside.hidden);
	}

	function getLineupAsides(eventId) {
		return document.querySelectorAll(
			`[id="cm-event-lineup-${eventId}"] .cm-event-lineup-layout__aside`
		);
	}

	function getRaffleDrawFromHTML(html) {
		if (!html) return null;
		const template = document.createElement("template");
		template.innerHTML = html;
		return template.content.querySelector(".cm-raffle-presentation-component");
	}

	function isRaffleDrawing(eventId) {
		return Array.from(getLineupAsides(eventId)).some((aside) =>
			aside.querySelector('.cm-raffle-presentation-component[data-drawing="1"]')
		) || Array.from(document.querySelectorAll(`[id="cm-current-presentation-${eventId}"]`)).some((container) =>
			container.querySelector('.cm-raffle-presentation-component[data-drawing="1"]')
		);
	}

	// A raffle draw uses the schedule sidebar as its audience screen. Moving the
	// existing component keeps delegated handlers, live polling and an in-flight
	// animation intact; an SSE-only lineup gets the same component from its HTML.
	function synchronizeRaffleDrawLayout(eventId, renderedHtml, isPresentationUpdate) {
		const asides = Array.from(getLineupAsides(eventId));
		if (!asides.length) return false;

		const renderedDraw = getRaffleDrawFromHTML(renderedHtml);
		const restoreDefaultAside = function () {
			asides.forEach((aside) => {
				aside.classList.remove("cm-event-lineup-layout__aside--raffle-draw");
				const holder = aside.querySelector(".cm-lineup-raffle-draw");
				if (holder) {
					holder.replaceChildren();
					holder.hidden = true;
				}
				const grid = aside.closest(".cm-event-lineup-layout__grid");
				if (grid) grid.classList.remove("cm-event-lineup-layout__grid--raffle-draw");
				updateAsideVisibility(aside.closest(".cm-live-container"));
			});
			return false;
		};

		// A presentation update is authoritative. Its ordinary or QR HTML must
		// remove a card left in the sidebar by the preceding raffle draw.
		if (isPresentationUpdate && !renderedDraw) return restoreDefaultAside();

		let source = Array.from(document.querySelectorAll(`[id="cm-current-presentation-${eventId}"]`))
			.map((container) => container.querySelector(".cm-raffle-presentation-component"))
			.find(Boolean);
		const activeComponent = asides
			.map((aside) => aside.querySelector(".cm-lineup-raffle-draw .cm-raffle-presentation-component"))
			.find(Boolean);
		const hasDraw = Boolean(renderedDraw || source || activeComponent);

		if (!hasDraw) return restoreDefaultAside();

		const nextRaffleId = renderedDraw && renderedDraw.dataset.raffleId;
		if (!source && activeComponent && (!nextRaffleId || activeComponent.dataset.raffleId === nextRaffleId)) {
			source = activeComponent;
		}
		if (!source) source = renderedDraw;

		asides.forEach((aside, index) => {
			let holder = aside.querySelector(".cm-lineup-raffle-draw");
			if (!holder) {
				holder = document.createElement("div");
				holder.className = "cm-lineup-raffle-draw";
				aside.appendChild(holder);
			}
			aside.hidden = false;
			aside.classList.add("cm-event-lineup-layout__aside--raffle-draw");
			holder.hidden = false;
			const grid = aside.closest(".cm-event-lineup-layout__grid");
			if (grid) {
				grid.classList.remove("cm-event-lineup-layout__grid--single");
				grid.classList.add("cm-event-lineup-layout__grid--raffle-draw");
			}
			// A page normally contains one schedule. Do not clone a live draw if an
			// editor placed multiple instances of the same schedule on that page.
			if (source && index === 0) holder.replaceChildren(source);
		});

		document.querySelectorAll(`[id="cm-current-presentation-${eventId}"]`).forEach((container) => {
			container.classList.add("cm-current-presentation--redundant");
		});
		return true;
	}

	function getUpcomingLineupItem(lineup) {
		const items = (Array.isArray(lineup) ? lineup : lineup ? Object.values(lineup) : [])
			.slice()
			.sort((a, b) => (a.start_time || "").localeCompare(b.start_time || ""));
		const active = items.find((item) => item.is_active == "1" || item.is_active === true);
		return items.find((item) => {
			const isActive = item.is_active == "1" || item.is_active === true;
			return !isActive && (!active || (item.start_time || "") >= (active.start_time || ""));
		}) || null;
	}

	function renderUpcomingLineupHTML(item) {
		if (!item) return "";
		const escapeHtml = (value) => String(value || "").replace(/[&<>\"']/g, (character) => ({
			"&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;",
		}[character]));
		const presenter = item.presenter ? `<p class="cm-lineup-upcoming__presenter">${escapeHtml(item.presenter)}</p>` : "";
		const description = item.description ? `<p class="cm-lineup-upcoming__description">${escapeHtml(item.description)}</p>` : "";
		return `<p class="cm-lineup-upcoming__eyebrow">Już niedługo</p>
			<p class="cm-lineup-upcoming__time">${escapeHtml((item.start_time || "").slice(0, 5))}</p>
			<h3>${escapeHtml(item.title)}</h3>${presenter}${description}`;
	}

	// A schedule and current-presentation shortcode can coexist on one page.
	// When the current block is a raffle QR, keep a single visible QR in the
	// schedule sidebar. If SSE changes it, copy its real URL and image first;
	// this never hides a fresh QR behind a stale raffle panel.
	function synchronizeRaffleSidebar(eventId) {
		const currentContainers = document.querySelectorAll(`[id="cm-current-presentation-${eventId}"]`);
		const sidebarPanels = document.querySelectorAll(
			`.cm-live-container[data-event-id="${eventId}"] .cm-event-lineup-layout .cm-lineup-raffle`
		);
		if (!currentContainers.length || !sidebarPanels.length) return;

		let currentQr = null;
		currentContainers.forEach((container) => {
			if (!currentQr) currentQr = container.querySelector(".cm-raffle-qr");
		});
		if (!currentQr) {
			if (!synchronizeRaffleDrawLayout(eventId)) {
				currentContainers.forEach((container) => container.classList.remove("cm-current-presentation--redundant"));
			}
			return;
		}

		const currentLink = currentQr.querySelector(".cm-raffle-qr__link");
		if (!currentLink || !currentLink.href) return;

		sidebarPanels.forEach((panel) => {
			let heading = panel.querySelector("h2");
			if (!heading) {
				heading = document.createElement("h2");
				heading.textContent = "Dołącz do losowania";
				panel.appendChild(heading);
			}
			let label = panel.querySelector(".cm-lineup-raffle__label");
			if (!label) {
				label = document.createElement("p");
				label.className = "cm-lineup-raffle__label";
				heading.after(label);
			}
			const title = currentQr.querySelector(".cm-raffle-qr__title");
			label.textContent = title ? title.textContent : "";
			let instruction = panel.querySelector(".cm-lineup-raffle__instruction");
			if (!instruction) {
				instruction = document.createElement("p");
				instruction.className = "cm-lineup-raffle__instruction";
				instruction.textContent = "Zeskanuj kod i zarejestruj się.";
				panel.appendChild(instruction);
			}
			let sidebarLink = panel.querySelector(".cm-lineup-raffle__link");
			if (!sidebarLink) {
				sidebarLink = document.createElement("a");
				sidebarLink.className = "cm-lineup-raffle__link";
				sidebarLink.textContent = currentLink.textContent;
				panel.appendChild(sidebarLink);
			}
			sidebarLink.href = currentLink.href;
			sidebarLink.textContent = currentLink.textContent;
			const currentImage = currentQr.querySelector(".cm-raffle-qr__image");
			let sidebarImage = panel.querySelector(".cm-lineup-raffle__image");
			if (currentImage && currentImage.src) {
				if (!sidebarImage) {
					sidebarImage = document.createElement("img");
					sidebarImage.className = "cm-lineup-raffle__image";
					instruction.before(sidebarImage);
				}
				sidebarImage.src = currentImage.src;
				sidebarImage.alt = currentImage.alt;
			} else if (sidebarImage) {
				sidebarImage.remove();
			}
			panel.hidden = false;
			updateAsideVisibility(panel.closest(".cm-live-container"));
		});
		currentContainers.forEach((container) => container.classList.add("cm-current-presentation--redundant"));
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
		const lineupArray = Array.isArray(lineup) ? lineup : lineup ? Object.values(lineup) : [];

		if (lineupArray.length === 0) {
			return '<div class="cm-lineup-list cm-lineup-list--empty"><p>Brak zaplanowanych prezentacji</p></div>';
		}

		const escapeHtml = (value) => String(value || "").replace(/[&<>\"']/g, (character) => ({
			"&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;",
		}[character]));
		const currentTime = new Date().toTimeString().slice(0, 8);

		const itemsHtml = lineupArray
			.slice()
			.sort((a, b) => (a.start_time || "").localeCompare(b.start_time || ""))
			.map((item) => {
				let endTime = item.end_time || "";
				if (!endTime && item.start_time && item.duration_minutes) {
					const start = new Date(`1970-01-01T${item.start_time}`);
					endTime = new Date(start.getTime() + item.duration_minutes * 60000).toTimeString().slice(0, 8);
				}

				const isCurrent = item.is_active == "1" || item.is_active === true;
				const isPast = Boolean(endTime && endTime < currentTime && !isCurrent);
				const startTime = (item.start_time || "").slice(0, 5);
				const formattedEndTime = endTime ? endTime.slice(0, 5) : "";
				const timeRange = formattedEndTime ? `${startTime} – ${formattedEndTime}` : startTime;
				const presenter = item.presenter ? `<p class="cm-presenter">${escapeHtml(item.presenter)}</p>` : "";
				const description = item.description ? `<p class="cm-description">${escapeHtml(item.description)}</p>` : "";
				const quickEvent = item.event_type === "quick" ? '<span class="cm-lineup-quick-event">Szybkie wydarzenie</span>' : "";

				return `<div class="cm-lineup-item${isCurrent ? " current" : isPast ? " past" : ""}">
					${isCurrent ? '<span class="cm-live-badge">Teraz</span>' : ""}
					<div class="cm-lineup-time"><div class="cm-lineup-time__range">${escapeHtml(timeRange)}</div></div>
					<div class="cm-lineup-content"><h4>${escapeHtml(item.title)}</h4>${presenter}${description}${quickEvent}</div>
				</div>`;
			})
			.join("");

		return `<div class="cm-lineup-list">${itemsHtml}</div>`;
	}
});
