# Event Manager

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![WordPress](https://img.shields.io/badge/WordPress-6.6%2B-blue)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple)

Wtyczka WordPress do zarządzania wydarzeniami i rejestracją uczestników z wykorzystaniem AJAX, Custom Post Types i Advanced Custom Fields.

Kompatybilność: Testowane na środowisku deweloperskim z WordPress 6.6+ i PHP 8.0+. Szczegóły w sekcji „Wymagania”.

---

## 📋 Spis treści

- [Opis](#-opis)
- [Wymagania](#-wymagania)
- [Instalacja](#-instalacja)
- [Konfiguracja](#-konfiguracja)
- [Funkcjonalności](#-funkcjonalności)
- [AJAX Endpoints](#-ajax-endpoints)
- [Struktura plików](#-struktura-plików)
- [Bezpieczeństwo](#-bezpieczeństwo)
- [Znane ograniczenia](#-znane-ograniczenia)
- [TODO / Rozwój](#-todo--rozwój)
- [Wsparcie](#-wsparcie)

---

## 📖 Opis

**Event Manager** to wtyczka WordPress umożliwiająca:
- Tworzenie i zarządzanie wydarzeniami
- Kategoryzację wydarzeń według miast
- Rejestrację uczestników bez przeładowania strony (AJAX)
- Kontrolę limitów miejsc na wydarzenie
- Przechowywanie rejestracji w post meta

---

## 🔧 Wymagania

### Środowisko
- **WordPress:** 6.6+
- **PHP:** 8.0+
- **MySQL:** 5.7+ lub MariaDB 10.2+

### Wtyczki wymagane
- **Advanced Custom Fields (ACF):** wersja 5.9+ lub ACF Pro
  - [Pobierz ACF Free](https://wordpress.org/plugins/advanced-custom-fields/)
  - [Pobierz ACF Pro](https://www.advancedcustomfields.com/pro/)

---

## 📦 Instalacja

### Metoda 1: Instalacja ręczna

1. **Pobierz wtyczkę** z repozytorium GitHub
2. **Wypakuj folder** `event-manager` do katalogu `/wp-content/plugins/`
3. **Zaloguj się** do panelu WordPress jako administrator
4. **Przejdź** do zakładki `Wtyczki` → `Zainstalowane wtyczki`
5. **Aktywuj** wtyczkę "Event Manager"

### Metoda 2: Upload przez panel WordPress

1. **Pobierz** plik `.zip` wtyczki
2. **Zaloguj się** do panelu WordPress
3. **Przejdź** do `Wtyczki` → `Dodaj nową`
4. **Kliknij** "Wyślij wtyczkę na serwer"
5. **Wybierz** plik `.zip` i kliknij "Zainstaluj"
6. **Aktywuj** wtyczkę

### Po aktywacji

Po aktywacji wtyczki:
- Zostanie utworzony Custom Post Type `event` (Wydarzenia)
- Zostanie utworzona taksonomia `city` (Miasta)
- Zostaną odświeżone reguły permalink
- W menu administracyjnym pojawi się pozycja "Wydarzenia"

---

## ⚙️ Konfiguracja

### 1. Zainstaluj ACF

Jeśli nie masz zainstalowanej wtyczki ACF, zobaczysz powiadomienie w panelu admina:

> **Event Manager:** Wtyczka wymaga zainstalowania i aktywacji wtyczki Advanced Custom Fields.

**Kroki:**
1. Przejdź do `Wtyczki` → `Dodaj nową`
2. Wyszukaj "Advanced Custom Fields"
3. Kliknij "Zainstaluj", a następnie "Aktywuj"

### 2. Dodaj pierwsze wydarzenie

1. W menu admin kliknij **Wydarzenia** → **Dodaj nowe**
2. Wypełnij **tytuł** wydarzenia
3. Dodaj **treść** (opcjonalnie)
4. Wypełnij **pola ACF**:
   - **Data i godzina rozpoczęcia** (wymagane)
   - **Limit uczestników** (opcjonalne, domyślnie: 50)
   - **Szczegółowy opis** (opcjonalnie)
5. Przypisz **miasto** w prawej kolumnie
6. Kliknij **Opublikuj**

### 3. Dodaj miasta

1. Przejdź do **Wydarzenia** → **Miasta**
2. Dodaj miasta, np.: Warszawa, Kraków, Wrocław
3. Przypisz miasta do wydarzeń

---

## ✨ Funkcjonalności

### Custom Post Type: `event`

- ✅ Dedykowany typ wpisu dla wydarzeń
- ✅ Widoczny w menu administracyjnym
- ✅ Wspiera Gutenberg i Classic Editor
- ✅ Permalinki: `/wydarzenia/nazwa-wydarzenia/`
- ✅ Ikona: 📅 (dashicons-calendar-alt)

### Taksonomia: `city`

- ✅ Kategoryzacja wydarzeń po miastach
- ✅ Nieherarchiczna (jak tagi)
- ✅ Widoczna w kolumnie admina
- ✅ Permalinki: `/miasto/nazwa-miasta/`

### Pola ACF

| Pole | Typ | Wymagane | Opis |
|------|-----|----------|------|
| **Data i godzina rozpoczęcia** | DateTimePicker | Tak | Data i godzina wydarzenia |
| **Limit uczestników** | Number | Nie | Maksymalna liczba miejsc |
| **Szczegółowy opis** | WYSIWYG | Nie | Dodatkowe informacje |

### Rejestracja uczestników

- ✅ Formularz rejestracji na stronie pojedynczego wydarzenia
- ✅ Walidacja po stronie frontendu (JavaScript) i backendu (PHP)
- ✅ Rejestracja przez AJAX - bez przeładowania strony
- ✅ Zabezpieczenia: nonce, sanityzacja, escape
- ✅ Przechowywanie w `post_meta` jako array
- ✅ Sprawdzanie limitów miejsc
- ✅ Blokada duplikatów (ten sam email)

### Struktura rejestracji (post meta)

```php
array(
    array(
        'name'          => 'Jan Kowalski',
        'email'         => 'jan@example.com',
        'registered_at' => '2025-11-15 10:30:00',
        'user_ip'       => '192.168.1.1',
    ),
    // ... kolejne rejestracje
)
```

---

## 🔌 AJAX Endpoints

### Endpoint: `register_event`

Registers a participant for an event.

URL:
```
/wp-admin/admin-ajax.php?action=register_event
```

Method:
```
POST
```

POST parameters:

| Param | Type | Required | Description |
|-------|------|----------|-------------|
| `action` | string | yes | `register_event` |
| `nonce` | string | yes | Security token (`event_registration_nonce`) |
| `event_id` | integer | yes | Event ID |
| `registration_name` | string | yes | Participant name |
| `registration_email` | string | yes | Participant email |

Success response (limit ustawiony):

```json
{
  "success": true,
  "data": {
    "message": "Dziękujemy! Rejestracja przebiegła pomyślnie.",
    "registered_name": "Jan Kowalski",
    "current_count": 15,
    "places_left": 35,
    "is_full": false
  }
}
```

Success response (brak limitu – `places_left` ma wartość null a `is_full` zawsze false):

```json
{
  "success": true,
  "data": {
    "message": "Dziękujemy! Rejestracja przebiegła pomyślnie.",
    "registered_name": "Jan Kowalski",
    "current_count": 15,
    "places_left": null,
    "is_full": false
  }
}
```

Error responses (examples – komunikat w kluczu `message`, pole `code` zwracane tylko w części endpointów wyszukiwarki):

```json
{ "success": false, "data": { "message": "Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie." } }
{ "success": false, "data": { "message": "Nieprawidłowe wydarzenie." } }
{ "success": false, "data": { "message": "Imię jest wymagane." } }
{ "success": false, "data": { "message": "Podaj prawidłowy adres e-mail." } }
{ "success": false, "data": { "message": "Ten adres e-mail jest już zarejestrowany na to wydarzenie." } }
{ "success": false, "data": { "message": "Przepraszamy, wszystkie miejsca są już zajęte." } }
{ "success": false, "data": { "message": "Wystąpił błąd podczas zapisywania. Spróbuj ponownie." } }
{ "success": false, "data": { "message": "Rejestracja na to wydarzenie została zamknięta." } }
```

Używane kody HTTP:
- 403 – nieprawidłowy lub brakujący nonce (błąd bezpieczeństwa)
- 404 – wydarzenie nie istnieje lub jest niedostępne
- 400 – błąd walidacji (imię / e-mail) lub wydarzenie już w przeszłości
- 409 – duplikat adresu e-mail lub osiągnięto limit miejsc
- 500 – wewnętrzny błąd przy zapisie rejestracji

### Endpoint: `event_search_ajax`

Fetch event list HTML based on filters.

URL:
```
/wp-admin/admin-ajax.php?action=event_search_ajax
```

Method:
```
POST
```

POST parameters:

| Param | Type | Required | Description |
|-------|------|----------|-------------|
| `action` | string | yes | `event_search_ajax` |
| `nonce` | string | yes | Security token (`event_search_nonce`) |
| `s_event` | string | no | Full-text search term |
| `city` | string | no | Comma-separated city slugs (e.g. `warszawa,krakow`) |
| `date_from` | string | no | Start date (YYYY-MM-DD) |
| `date_to` | string | no | End date (YYYY-MM-DD) |
| `paged` | int | no | Page number (default 1) |

Success response:

```json
{
   "success": true,
   "data": {
      "html": "<div class=\"event-search-results\">…</div>",
      "total": 23,
      "max_pages": 3,
      "current_page": 1
   }
}
```

Empty results example (brak dopasowań):

```json
{
  "success": true,
  "data": {
    "html": "<div class=\"event-search-no-results\"><p>Brak wyników.</p></div>",
    "total": 0,
    "max_pages": 0,
    "current_page": 1
  }
}
```

Errors:
- Invalid nonce → `{ success:false, data:{ message: "Błąd bezpieczeństwa.", code: "invalid_nonce" } }`
- Invalid date format → `{ success:false, data:{ message: "Nieprawidłowy format daty." } }`
- From date after to date → `{ success:false, data:{ message: "Data początkowa nie może być późniejsza." } }`

### Endpoint: `event_search_nonce`

Return a fresh nonce for the search UI (used by the frontend to recover after back/forward cache or long idle times).

URL:
```
/wp-admin/admin-ajax.php?action=event_search_nonce
```

Method:
```
POST
```

Response:

```json
{ "success": true, "data": { "nonce": "…" } }
```

---

## 📁 Struktura plików

```
event-manager/
│
├── event-manager.php              # Główny plik wtyczki
├── uninstall.php                  # Czyszczenie opcji przy usunięciu
│
├── includes/                      # Logika PHP
│   ├── cpt-registration.php       # Rejestracja CPT i taksonomii
│   ├── acf-fields.php             # Definicja pól ACF
│   ├── ajax-registration.php      # Logika endpointu rejestracji
│   ├── ajax-search.php            # Logika wyszukiwarki
│   ├── ajax.php                   # Centralne add_action dla AJAX
│   ├── event-search.php           # Shortcode [event_search]
│   ├── utils.php                  # Helper (IP itd.)
│   └── logger.php                 # Proste logowanie do debug.log
│
├── assets/                        # Zasoby frontendowe
│   ├── js/
│   │   ├── event-register.js      # JS rejestracji
│   │   └── event-search.js        # JS wyszukiwarki
│   └── css/
│       └── style.css              # Style wtyczki
│
├── templates/                     # Szablony frontendu
│   ├── single-event.php           # Szablon pojedynczego wydarzenia (partial)
│   └── search-form.php            # Formularz wyszukiwarki

```

### Strona wyszukiwarki

Przy aktywacji wtyczka automatycznie tworzy (lub aktualizuje) stronę z shortcode `[event_search]` pod slugiem `eventy` i tytułem "Wydarzenia". Unika konfliktu z archiwum CPT `/wydarzenia` używając innego slugu. ID strony zapisywane jest w opcji `event_manager_events_page_id`.

Możesz wyłączyć automatyczne tworzenie strony dodając w motywie:

```php
add_filter( 'event_manager_create_page_on_activate', '__return_false' );
```

Jeśli wyłączysz ten mechanizm, utwórz stronę ręcznie i wstaw shortcode `[event_search]`.

---

## 🔒 Bezpieczeństwo

Wtyczka implementuje następujące zabezpieczenia:

### 1. Nonce
- ✅ Token `event_registration_nonce` generowany przez `wp_create_nonce()`
- ✅ Weryfikacja przez `wp_verify_nonce()`

### 2. Sanityzacja danych wejściowych
- ✅ `sanitize_text_field()` - dla imienia
- ✅ `sanitize_email()` - dla emaila
- ✅ `absint()` - dla event_id

### 3. Walidacja
- ✅ Sprawdzenie formatu email (`is_email()`)
- ✅ Walidacja istnienia wydarzenia
- ✅ Sprawdzenie limitów
- ✅ Detekcja duplikatów

### 4. Escaping danych wyjściowych
- ✅ `esc_html()` - dla tekstu
- ✅ `esc_attr()` - dla atrybutów HTML
- ✅ `wp_kses_post()` - dla treści HTML

### 5. Dodatkowe zabezpieczenia
- ✅ Blokada bezpośredniego dostępu do plików PHP
- ✅ Walidacja IP użytkownika
- ✅ Zabezpieczenie przed XSS w JavaScript


---

## 👨‍💻 Autor

Stworzone dla zadania rekrutacyjnego.

---

# event-manager

## ▶️ Uruchom w WordPress Playground

Możesz szybko przetestować wtyczkę w przeglądarce używając pliku `blueprint.json` (instaluje ACF i pobiera wtyczkę z GitHub):

- Otwórz: https://playground.wordpress.net/
- W menu wybierz „Open” → „From URL” i wklej URL zipa repo lub użyj „Import from GitHub” wskazując `a-szyszlo/event-manager`.
- Alternatywnie, skopiuj zawartość `blueprint.json` do edytora po prawej i kliknij „Run”.

Uwaga: Blueprint ma ustawione `features.networking: true`, aby umożliwić pobieranie z GitHub.

### Troubleshooting
- Jeśli pojawia się błąd pobierania z GitHub, spróbuj ponownie (chwilowe ograniczenia rate-limit lub CORS) lub odśwież Playground.
- Upewnij się, że folder docelowy wtyczki to `event-manager` — blueprint wymusza to przez `options.targetFolderName`.
- Gdy ACF się nie instaluje, uruchom blueprint ponownie; źródło ACF to `wordpress.org/plugins`.

## Lokalnie (Local WP / dowolny WP)
1. Skopiuj folder `event-manager` do `wp-content/plugins/`.
2. Aktywuj wtyczkę w panelu `Wtyczki`.
3. Zainstaluj i aktywuj „Advanced Custom Fields”.
