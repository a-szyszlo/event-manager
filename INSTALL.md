# Instrukcja instalacji Event Manager

## 🚀 Metoda 1: Instalacja na WordPress Playground (Najszybsza)

WordPress Playground to darmowe środowisko testowe WordPress działające w przeglądarce - bez potrzeby instalacji serwera!

### Krok 1: Otwórz Playground

Kliknij w poniższy link, aby automatycznie załadować WordPress z wtyczką Event Manager:

**🔗 [Uruchom Event Manager w WordPress Playground](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/a-szyszlo/event-manager/main/blueprint.json)**


### Krok 2: Sprawdź czy wtyczka jest aktywna

1. Przejdź do **Wtyczki** → **Zainstalowane wtyczki**
2. Upewnij się, że **Event Manager** i **Advanced Custom Fields** są aktywne

### Krok 3: Dodaj przykładowe wydarzenie

1. W menu kliknij **Wydarzenia** → **Dodaj nowe**
2. Wypełnij:
   - **Tytuł:** "Konferencja WordPress 2025"
   - **Treść:** "Największe wydarzenie dla deweloperów WP w Polsce!"
   - **Data i godzina:** Wybierz przyszłą datę
   - **Limit uczestników:** 50
   - **Miasto:** Dodaj "Warszawa" w prawej kolumnie
3. Kliknij **Opublikuj**

### Krok 4: Zobacz wydarzenie na froncie

1. Kliknij **Zobacz wydarzenie** lub
2. Przejdź do `/wydarzenia/konferencja-wordpress-2025/`
3. Przetestuj formularz rejestracji!

---

## 💻 Metoda 2: Instalacja na lokalnym WordPress

### Wymagania

- WordPress 5.8+
- PHP 7.4+
- Wtyczka ACF (Advanced Custom Fields)

### Kroki instalacji

#### 1. Sklonuj repozytorium

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/a-szyszlo/event-manager.git
```

#### 2. Zainstaluj ACF

**Opcja A: Przez panel WordPress**
1. Przejdź do **Wtyczki** → **Dodaj nową**
2. Wyszukaj "Advanced Custom Fields"
3. Kliknij **Zainstaluj** → **Aktywuj**

**Opcja B: Przez WP-CLI**
```bash
wp plugin install advanced-custom-fields --activate
```

#### 3. Aktywuj Event Manager

**Opcja A: Przez panel**
1. Przejdź do **Wtyczki**
2. Znajdź "Event Manager"
3. Kliknij **Aktywuj**

**Opcja B: Przez WP-CLI**
```bash
wp plugin activate event-manager
```

#### 4. Sprawdź instalację

Otwórz stronę i sprawdź czy:
- ✅ W menu admina pojawiła się pozycja "Wydarzenia"
- ✅ Możesz dodać nowe wydarzenie
- ✅ Pola ACF są widoczne w edytorze

---

## 🧪 Metoda 3: Local by Flywheel / XAMPP / MAMP

### Local by Flywheel (Zalecane)

1. **Pobierz Local:** https://localwp.com/
2. **Stwórz nową stronę:**
   - Nazwa: Event Manager Demo
   - Environment: Preferowany (PHP 8.0+)
3. **Zainstaluj WordPress**
4. **Wykonaj kroki z Metody 2**

### XAMPP / MAMP

1. Zainstaluj XAMPP/MAMP
2. Umieść WordPress w `htdocs/` (XAMPP) lub `htdocs/` (MAMP)
3. Wykonaj standardową instalację WordPress
4. Wykonaj kroki z Metody 2

---

## 📝 Testowanie wtyczki
Minimalne sprawdzenie poprawności po instalacji:

1. Dodaj wydarzenie (patrz sekcja Konfiguracja) i zobacz je na froncie.
2. Wypełnij formularz rejestracji i upewnij się, że pojawia się komunikat sukcesu.
3. (Opcjonalnie) Spróbuj zapisać się drugi raz tym samym emailem – powinien pojawić się komunikat o duplikacie.
4. (Opcjonalnie) Ustaw limit np. 1 i sprawdź, że druga rejestracja jest blokowana.

Dodatkowe scenariusze i szczegółowe testy znajdziesz w `README.md`.

---

## 🔍 Debugowanie
Skrócone wskazówki:

- Pola ACF niewidoczne: upewnij się, że wtyczka ACF jest aktywna.
- 404 na wydarzeniu: zapisz ponownie ustawienia permalinków.
- AJAX nie działa: sprawdź konsolę JS i zakładkę Network (żądania do `admin-ajax.php`).
- Uprawnienia: testuj jako administrator.

Pełne wskazówki debugowania w `README.md`.

---

## 🆘 Wsparcie

Masz problem? Sprawdź:

1. **README.md** - Główna dokumentacja
2. **TECHNICAL_DOCUMENTATION.md** - Dokumentacja techniczna
3. **GitHub Issues** - Zgłoś problem
4. **WordPress Debug Log:**

```php
// wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Log znajdziesz w: `wp-content/debug.log`

**Gotowe! Możesz teraz korzystać z Event Manager! 🎉**

## 🧪 Dodatkowe testy wyszukiwarki
Szczegółowe testy (filtry, daty, paginacja, nonce) opisane są w `README.md`.

## 🗑️ Odinstalowanie

Usunięcie wtyczki wywołuje `uninstall.php`, który czyści zapisane opcje (np. `event_manager_events_page_id`). Rejestracje w `post_meta` pozostają zachowane.
