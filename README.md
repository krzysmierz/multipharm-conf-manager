# Conference Manager

Wtyczka WordPress do prowadzenia wydarzeń konferencyjnych: zarządzania wydarzeniami i ich programem, quizami dla uczestników, plikami oraz kodami QR.

## Najważniejsze funkcje

- wydarzenia ze statusem szkic, aktywne, wstrzymane lub zakończone, także wielodniowe (do 7 dni);
- program z prezentacjami i szybkimi punktami (np. przerwa, lunch, networking), godziną, czasem trwania, prowadzącym, opisem, kolejnością i kontrolą kolizji czasowych;
- ręczne uruchamianie prezentacji oraz aktualizacje na żywo aktualnej prezentacji i programu;
- pliki prezentacji i obrazy przypisane do wydarzenia, z konfigurowalnym limitem oraz typami plików;
- quizy powiązane z wydarzeniem lub prezentacją: pytania jednokrotnego i wielokrotnego wyboru albo tekstowe, odpowiedzi uczestników, ranking, wyniki szczegółowe i eksport CSV;
- ekran quizu aktualizowany na żywo, z przełączaniem między kodem QR a wynikami oraz opcjonalnym automatycznym przełączaniem;
- kody QR do wydarzeń, quizów i prezentacji.

## Wymagania i instalacja

- WordPress 5.8 lub nowszy (wymaganie zadeklarowane w nagłówku wtyczki);
- konto administratora WordPress (`manage_options`) do obsługi panelu;
- zapisywalny katalog uploadów WordPress — wtyczka tworzy w nim `conference-manager/` dla prezentacji, obrazów i kodów QR;
- PHP GD do lokalnego generowania PNG kodów QR. Dołączona biblioteka QR deklaruje także PHP 8.2+ i rozszerzenie `mbstring`, chociaż nagłówek wtyczki deklaruje minimum PHP 7.4. W środowisku produkcyjnym używaj PHP 8.2+ przy korzystaniu z QR.

Skopiuj cały katalog projektu do `wp-content/plugins/conference-manager/`, a następnie aktywuj **Conference Manager** w panelu WordPress. Aktywacja tworzy własne tabele bazy danych i katalogi uploadów; nie ma osobnej komendy Composer, npm ani procesu budowania określonego dla tej wtyczki.

## Obsługa

Po aktywacji przejdź do **Conference Manager** w kokpicie WordPress.

1. W sekcji **Wydarzenia** utwórz wydarzenie, ustaw datę, godziny i status.
2. W edycji wydarzenia uzupełnij program, dni wydarzenia, quizy, pliki i kody QR. Prezentację można oznaczyć jako aktywną; to aktualizuje widoki na żywo.
3. W **Ustawieniach** określ rozmiar QR, limit i dozwolone rozszerzenia plików, limit czasu quizu oraz automatyczne przełączanie prezentacji.

### Wyświetlanie na stronie

Wstaw odpowiedni shortcode do treści strony lub wpisu, zastępując identyfikator własnym ID:

```text
[cm_event id="1"]
[cm_event_lineup event_id="1" day="auto"]
[cm_current_presentation event_id="1"]
[cm_quiz id="1"]
[cm_quiz_display]
```

`cm_event_lineup` przyjmuje `day="auto"` (aktywny dzień, domyślnie) albo numer dnia. `cm_quiz_display` służy jako dedykowany ekran quizu: bez parametru adresu wyświetla aktualny quiz i jego tryb, a adres z `?cm_quiz=ID` udostępnia aktywny quiz uczestnikowi. Kody QR prowadzą również do widoków przez parametry `cm_event`, `cm_quiz` lub `cm_presentation`.

## Rozwój

Kod wtyczki znajduje się przede wszystkim w `includes/`, `admin/` i `public/`; biblioteki do QR są już dołączone w `lib/`. Repozytorium nie definiuje głównej konfiguracji zależności ani automatycznych testów, więc zmiany należy sprawdzać w działającej instalacji WordPress.

## Licencja

GPL-2.0-or-later — zgodnie z nagłówkiem wtyczki.
