# StaffEase Pro

StaffEase Pro e una web app PHP/MySQL per la gestione di turni, presenze, richieste interne e pianificazione operativa.

Questo README e scritto come documento di studio per:

- preparare l'esposizione d'esame,
- compilare dossier tecnici/funzionali,
- ripassare architettura, regole di business e flussi per ruolo.

## 1) Obiettivo Del Progetto

Obiettivo principale: costruire una piattaforma semplice, leggibile e dimostrabile che copra un ciclo reale di workforce management.

Punti chiave:

- architettura chiara (front controller + routing + controller + model PDO),
- interfaccia unica dashboard con modali coerenti,
- regole di business esplicite (assegnazioni, disponibilita, timbratura, vincoli IP),
- comportamento coerente su desktop e mobile.

## 2) Stack Tecnologico

- Backend: PHP (render server-side + endpoint JSON)
- Database: MySQL (accesso via PDO)
- Frontend: Vanilla JavaScript + CSS modulare
- Ambiente locale: MAMP / `php -S localhost:8000`

## 3) Architettura Applicativa

### Entry Point E Routing

- `index.php`: front controller unico.
- `app/router.php`: mappa `route` -> controller/view.

Flusso:

1. `index.php` legge `route`.
2. Carica controller o vista tramite router.
3. Renderizza layout condiviso (header, contenuto, modali/script condizionali).

### Struttura Directory

- `backend/`: bootstrap, helper, controller, model
- `backend/controllers/`: logica pagina + API JSON
- `backend/models/`: accesso dati PDO
- `app/layout/`: blocchi UI condivisi
- `assets/js/`: logica interattiva dashboard/employee
- `assets/css/`: stile generale, admin, calendar, responsive
- `public/views/`: pagine renderizzate per ruolo
- `db/schema.sql`: schema completo
- `db/migrations/`: migrazioni incrementali

## 4) Ruoli E Permessi

Ruoli supportati:

- `super_admin`
- `admin`
- `department_manager`
- `employee`

Scoping generale:

- Super Admin: visione globale multi-company.
- Admin: visione della propria company.
- Department Manager: visione del proprio reparto.
- Employee: solo spazio personale (`my-space`), solo propri turni e firma presenza.

## 5) Funzionalita Principali

### 5.1 Dashboard Unificata

- Modulo dashboard unico con sezione settings e planner.
- CRUD entities in shell comune per mantenere UX uniforme.
- Feedback visuale unificato per successo/errore/conferma.

### 5.2 Gestione Company, Reparti, Utenti

- Company directory con conteggi aggregati corretti.
- Reparti con icona/colore e gestione responsabile reparto (`head_user_id`).
- Utenti con ruoli, stato, appartenenza azienda/reparto.

### 5.3 Gestione Turni

- Catalogo turni per reparto.
- Attributi visuali (icona, colore, descrizione, orari).
- Supporto planning su range date e visualizzazione su calendario.

### 5.4 Calendar Planner (Core)

- Viste temporali: week / fortnight / month / year.
- Overlay full-screen giornaliero con dettagli assegnazioni.
- Navigazione giorno precedente/successivo.
- Evidenza open slots e assegnazioni presenti.

### 5.5 Disponibilita Impiegati E Regole

- Regole individuali:
  - giorni settimana non disponibili,
  - date specifiche non disponibili (rest/vacation/sick/leave).
- Le regole vengono rispettate in:
  - auto-assegnazione,
  - drag and drop,
  - assegnazioni da pannello settings.

### 5.6 Auto-Assign Avanzato

Nuova logica (aggiornata):

- rimossi limiti mensili (ore/giorni),
- introdotti:
  - `Minimum employees / shift / day`
  - `Maximum employees / shift / day`
- algoritmo con priorita ai gruppi shift-giorno sotto copertura,
- rispetto vincoli di disponibilita individuale,
- output con `groups_below_min` quando il minimo non e raggiungibile.

### 5.7 Presenze Con Firma Touchscreen

Spazio employee (`route=my-space`) mobile-first:

- mostra solo turni personali,
- firma presenza solo per turni del giorno corrente,
- pad touchscreen (canvas) per firma,
- firma salvata in `digital_signatures` e collegata a `attendances`.

### 5.8 Vincolo IP Wi-Fi Aziendale

In settings (Users tab), Super Admin/Admin puo impostare IP autorizzato della company:

- campo vuoto -> firma consentita da qualsiasi rete,
- campo valorizzato -> firma consentita solo da IP configurato.

Questa regola protegge la timbratura fuori sede.

### 5.9 Flash/Alert Unificati

- Messaggi utente consolidati in unico modulo stile.
- Conferme eliminazioni/azioni critiche con stesso componente UI.
- Testi utente standardizzati in inglese nell'interfaccia.

## 6) API JSON Principali

Dispatcher:

- `backend/controllers/ApiDispatcher.php`

Route:

- `route=api-dashboard`
- `route=api-companies`
- `route=api-departments`
- `route=api-users`
- `route=api-shifts`

Esempi azioni importanti:

- `auto_assign_open`
- `clear_assignments_scope`
- `set_signature_ip`
- CRUD utenti/reparti/turni

## 7) Database E Migrazioni

File principali:

- [db/schema.sql](db/schema.sql)
- [db/migrations/20260528_add_departments_head_user_id.sql](db/migrations/20260528_add_departments_head_user_id.sql)
- [db/migrations/20260601_add_icon_color.up.sql](db/migrations/20260601_add_icon_color.up.sql)
- [db/migrations/20260601_add_icon_color.down.sql](db/migrations/20260601_add_icon_color.down.sql)

Tabelle chiave da conoscere per l'esame:

- `companies`
- `departments`
- `users`
- `shifts`
- `user_shifts`
- `attendances`
- `digital_signatures`
- `requests`

## 8) Flussi Operativi Per Ruolo (Workflow)

### Super Admin

1. Seleziona company in settings.
2. Configura reparti, utenti, turni e policy IP.
3. Verifica copertura globale tramite planner.

### Admin

1. Gestisce utenti/reparti/turni della propria company.
2. Imposta disponibilita individuali e lancia auto-assign.
3. Controlla gruppi sotto minimo e corregge manualmente.

### Department Manager

1. Visualizza il proprio reparto nel planner.
2. Esegue aggiustamenti giornalieri sulle assegnazioni.
3. Monitora richieste e disponibilita del team.

### Employee

1. Accede a `my-space`.
2. Visualizza solo turni personali.
3. Firma presenza da mobile touchscreen.
4. Invia richieste (leave/permission/coverage/document).

## 9) Come E Stato Sviluppato (Metodologia)

Approccio incrementale per feature verticali:

1. Definizione regola di business.
2. Aggiornamento view/layout.
3. Implementazione JS modulo dedicato.
4. Aggiornamento controller/API + validazioni server-side.
5. Verifica sintassi (`php -l`, `node --check`) e diagnostica editor.
6. Test end-to-end su UI.

Principi seguiti:

- riduzione duplicazioni,
- coerenza naming e UX,
- fallback sicuri lato server,
- compatibilita con schema legacy via migrazioni mirate.

## 10) Pulizia Tecnica E Coerenza

Durante la revisione sono stati eliminati elementi ridondanti/obsoleti e risolte discrepanze:

- rimosso template placeholder duplicato che causava ID duplicati nel DOM,
- consolidata documentazione nel presente README,
- mantenuto solo codice effettivamente in uso nel flusso dashboard/employee.

## 11) Script Di Presentazione (Pronto Esame)

Sequenza consigliata (8-10 minuti):

1. Architettura: `index.php` -> router -> controller -> view.
2. Ruoli e scoping dati.
3. Settings panel: company/reparti/utenti/turni.
4. Planner calendario: overlay giorno + assegnazioni.
5. Regole disponibilita impiegato.
6. Auto-assign min/max per shift-giorno.
7. Policy IP Wi-Fi aziendale.
8. Login employee e firma touchscreen.
9. Chiusura: benefici, limiti attuali, evoluzioni future.

## 12) Limiti Attuali E Miglioramenti Futuri

Limiti attuali:

- niente framework di test automatico integrato,
- alcune operazioni legacy possono essere ulteriormente semplificate,
- logging funzionale migliorabile.

Evoluzioni future:

- test automatici API/UI,
- reportistica avanzata presenze/copertura,
- supporto reti CIDR per policy IP,
- audit trail esteso sulle modifiche planner.

## 13) Avvio Rapido

1. Configura DB in `config/database.php`.
2. Importa `db/schema.sql`.
3. Applica migrazioni in `db/migrations/` se necessario.
4. Avvia server:

```bash
php -S localhost:8000
```

5. Apri:

- `http://localhost:8000/?route=login`

---

Questo file puo essere usato direttamente come base per dossier tecnico, dossier funzionale e script di esposizione orale.
