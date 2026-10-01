# Conference Manager

## Własny CSS wydarzenia

W zakładce **Podstawowe informacje** zaufany administrator z uprawnieniem WordPress `edit_css` może ustawić osobny CSS dla publicznego harmonogramu i podstrony losowania (w tym formularza rejestracji). Reguły należy scope’ować klasą `.cm-event-theme-ID`, na przykład `.cm-event-theme-12 .cm-lineup-item { border-radius: 0; }`; wtyczka nie przepisuje selektorów automatycznie. Repozytorium nie ma pipeline’u Sass, więc pola obsługują bezpośrednio CSS; w panelu używany jest wbudowany edytor WordPress z tekstowym fallbackiem.

Wtyczka WordPress do prowadzenia wydarzeń konferencyjnych: zarządzania wydarzeniami i ich programem, quizami dla uczestników, plikami oraz kodami QR.

## Najważniejsze funkcje

- wydarzenia ze statusem szkic, aktywne, wstrzymane lub zakończone, także wielodniowe (do 7 dni);
- program z prezentacjami i szybkimi punktami (np. przerwa, lunch, networking), godziną, czasem trwania, prowadzącym, opisem, kolejnością i kontrolą kolizji czasowych;
- ręczne uruchamianie prezentacji oraz aktualizacje na żywo aktualnej prezentacji i programu;
- pliki prezentacji i obrazy przypisane do wydarzenia, z konfigurowalnym limitem oraz typami plików;
- quizy powiązane z wydarzeniem lub prezentacją: pytania jednokrotnego i wielokrotnego wyboru albo tekstowe, odpowiedzi uczestników, ranking, wyniki szczegółowe i eksport CSV;
- ekran quizu aktualizowany na żywo, z przełączaniem między kodem QR a wynikami oraz opcjonalnym automatycznym przełączaniem;
- kody QR do wydarzeń, quizów i prezentacji;
- rejestracja uczestników przez dedykowane, tokenowe QR oraz wielokrotne losowania z trwałą historią zwycięzców.

## Wymagania i instalacja

- WordPress 5.8 lub nowszy (wymaganie zadeklarowane w nagłówku wtyczki);
- konto administratora WordPress (`manage_options`) do obsługi panelu;
- zapisywalny katalog uploadów WordPress — wtyczka tworzy w nim `conference-manager/` dla prezentacji, obrazów i kodów QR;
- PHP GD do lokalnego generowania PNG kodów QR. Dołączona biblioteka QR deklaruje także PHP 8.2+ i rozszerzenie `mbstring`, chociaż nagłówek wtyczki deklaruje minimum PHP 7.4. W środowisku produkcyjnym używaj PHP 8.2+ przy korzystaniu z QR.

Zbuduj paczkę poleceniem `scripts/build-plugin.sh`, a następnie w panelu WordPress wybierz **Wtyczki → Dodaj nową → Wyślij wtyczkę na serwer** i wskaż `build/conference-manager-<wersja>.zip`. Po instalacji aktywuj **Conference Manager**. Nie kopiuj surowego katalogu projektu: zawiera on wyłączone z dystrybucji kopie historyczne, które nie są częścią instalowalnej wtyczki.

Przy naprawie istniejącej instalacji zastąp cały katalog `wp-content/plugins/conference-manager/` plikami z poprawnej paczki ZIP (albo usuń go przez FTP/SFTP i wgraj paczkę ponownie), **bez klikania „Usuń” w panelu WordPress**. Zastąpienie plików nie usuwa tabel ani uploadów; akcja „Usuń” uruchamia `uninstall.php`, który celowo usuwa dane wtyczki.

## Obsługa

Po aktywacji przejdź do **Conference Manager** w kokpicie WordPress.

1. W sekcji **Wydarzenia** utwórz wydarzenie, ustaw datę, godziny i status.
2. W edycji wydarzenia uzupełnij program, dni wydarzenia, quizy, pliki i kody QR. Prezentację można oznaczyć jako aktywną; to aktualizuje widoki na żywo.
3. W zakładce **Losowanie** utwórz osobny kod rejestracji dla danego losowania. QR otwiera samodzielny formularz imienia i nazwiska; uczestnik może zgłosić tę samą znormalizowaną parę tylko raz dla tego kodu. Po potwierdzonym zapisie formularz pokazuje komunikat, udostępnia przycisk **Strona losowania** do dedykowanego ekranu danego losowania, a po 5 sekundach przekierowuje uczestnika na stronę główną WordPress. Z tej samej zakładki wybierasz zwycięzcę dowolnie wiele razy i przeglądasz historię. Każde losowanie obejmuje całą listę, również wcześniejszych zwycięzców.
4. W **Ustawieniach** określ rozmiar QR, limit i dozwolone rozszerzenia plików, limit czasu quizu oraz automatyczne przełączanie prezentacji.

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

Kod wtyczki znajduje się przede wszystkim w `includes/`, `admin/` i `public/`; biblioteki do QR są już dołączone w `lib/`. Testy reguł rejestracji i losowań uruchamia się przez `php tests/raffle-test.php`, a generowania i odczytu QR przez `php tests/qr-test.php` (PHP 8.2+, GD, mbstring). Testy domeny korzystają z atrap WordPressa i bazy; migrację oraz pełny przepływ panelu/formularza należy dodatkowo sprawdzić na testowej instalacji WordPress z MySQL.

Wersja 0.9.59 ładuje kontroler losowania również na stronie harmonogramu, dzięki czemu karta wstawiona do bocznego panelu przez SSE odtwarza kolejne wyniki. Wersja 0.9.58 po udanej rejestracji do losowania przekierowuje uczestnika po 5 sekundach na stronę główną; przycisk **Strona losowania** anuluje oczekiwanie i przechodzi od razu na dedykowany ekran losowania. Wersja 0.9.55 dodaje własny CSS dla harmonogramu i widoków losowania danego wydarzenia oraz rozszerza moduł losowania o prezentację na żywo i rejestrację przez QR. Wersja 0.9.8 dodaje zakładkę **Losowanie**. Wersja 0.9.9 naprawia paczkę instalacyjną, aby zawierała tylko jeden bootstrap wtyczki. Aktualizacja schematu bazy uruchamia się po wejściu do panelu administracyjnego. Nie trzeba tworzyć strony ani zapisywać ponownie bezpośrednich odnośników. Kanonicznym źródłem adresów losowania są metody `CM_Raffle::get_registration_url()` i `CM_Raffle::get_presentation_url()`; korzystają z `home_url('/')`, więc uwzględniają konfigurację adresu WordPress. QR rejestracji generowany jest lokalnie i nie korzysta z zewnętrznego serwisu. Blokada duplikatów opiera się na imieniu i nazwisku, więc dwie osoby o identycznych danych nie mogą zgłosić się do tego samego kodu.

## Licencja

GPL-2.0-or-later — zgodnie z nagłówkiem wtyczki.
