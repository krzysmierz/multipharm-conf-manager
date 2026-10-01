(function () {
    'use strict';

    var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var transitionDuration = 280;

    function initialiseSlider(slider) {
        var track = slider.querySelector('.cm-raffle-icons__track');
        var previous = slider.querySelector('[data-cm-avatar-previous]');
        var next = slider.querySelector('[data-cm-avatar-next]');
        var selection = slider.querySelector('[data-cm-avatar-selection]');
        var moving = false;
        var pointerStartX = null;
        var suppressClickUntil = 0;

        if (!track || !previous || !next) {
            return;
        }

        function choices() {
            return Array.prototype.slice.call(track.querySelectorAll('.cm-raffle-icon-choice'));
        }

        function inputFor(choice) {
            return choice.querySelector('input[type="radio"]');
        }

        function announce(input) {
            if (selection) {
                selection.textContent = input.getAttribute('aria-label') || '';
            }
        }

        function placeChoiceFirst(choice) {
            while (track.firstElementChild !== choice) {
                track.appendChild(track.firstElementChild);
            }
        }

        function selectChoice(choice) {
            var input = inputFor(choice);
            if (!input.checked) {
                input.checked = true;
            }
            announce(input);
        }

        function finishMove(direction) {
            if (!moving) {
                return;
            }

            track.classList.remove('is-animating');
            if (direction > 0) {
                track.appendChild(track.firstElementChild);
            }
            track.style.transform = 'translate3d(0, 0, 0)';
            moving = false;
        }

        function move(direction) {
            var currentChoices = choices();
            var target;

            if (moving || currentChoices.length < 2) {
                return;
            }

            target = direction > 0 ? currentChoices[1] : currentChoices[currentChoices.length - 1];
            selectChoice(target);
            moving = true;

            if (direction < 0) {
                track.insertBefore(target, track.firstElementChild);
                track.style.transform = 'translate3d(-100%, 0, 0)';
                track.getBoundingClientRect();
            }

            if (prefersReducedMotion) {
                finishMove(direction);
                return;
            }

            track.classList.add('is-animating');
            track.style.transform = direction > 0 ? 'translate3d(-100%, 0, 0)' : 'translate3d(0, 0, 0)';
            window.setTimeout(function () {
                finishMove(direction);
            }, transitionDuration + 80);
        }

        function showSelectedChoice(input) {
            var choice = input.closest('.cm-raffle-icon-choice');
            if (!choice || moving) {
                return;
            }
            placeChoiceFirst(choice);
            track.style.transform = 'translate3d(0, 0, 0)';
        }

        track.querySelectorAll('input[type="radio"]').forEach(function (input) {
            input.addEventListener('focus', function () {
                showSelectedChoice(input);
            });
            input.addEventListener('change', function () {
                announce(input);
                showSelectedChoice(input);
            });
            if (input.checked) {
                announce(input);
                showSelectedChoice(input);
            }
        });

        if (!track.querySelector('input[type="radio"]:checked') && choices().length) {
            selectChoice(choices()[0]);
        }

        if (choices().length < 2) {
            return;
        }

        slider.classList.add('is-enhanced');
        previous.hidden = false;
        next.hidden = false;

        previous.addEventListener('click', function () {
            move(-1);
        });
        next.addEventListener('click', function () {
            move(1);
        });

        track.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
                event.preventDefault();
                move(-1);
            } else if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
                event.preventDefault();
                move(1);
            }
        });

        track.addEventListener('pointerdown', function (event) {
            if (event.pointerType !== 'mouse') {
                pointerStartX = event.clientX;
            }
        });
        track.addEventListener('pointerup', function (event) {
            var distance;
            if (pointerStartX === null) {
                return;
            }
            distance = event.clientX - pointerStartX;
            pointerStartX = null;
            if (Math.abs(distance) > 32) {
                suppressClickUntil = Date.now() + 400;
                move(distance < 0 ? 1 : -1);
            }
        });
        track.addEventListener('pointercancel', function () {
            pointerStartX = null;
        });
        track.addEventListener('click', function (event) {
            if (Date.now() < suppressClickUntil) {
                event.preventDefault();
                event.stopPropagation();
            }
        }, true);

    }

    document.querySelectorAll('[data-cm-avatar-slider]').forEach(initialiseSlider);
}());
