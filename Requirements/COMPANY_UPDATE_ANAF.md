# Company Update from ANAF - Requirements

## Project Context

| Item | Value |
|------|-------|
| **Organization** | [peviitor-ro](https://github.com/peviitor-ro) |
| **Repository** | [peviitor_core](https://github.com/peviitor-ro/peviitor_core) |
| **Core** | `company` |

> **Important**: Acest document trebuie actualizat dacă apar modificări în schema SOLR sau structura proiectului `peviitor_core`.

---

## Overview
Automatizarea completării datelor companiilor din core-ul SOLR `company` folosind API-ul ANAF.

## ANAF API

**Endpoint:**
```
https://webservicesp.anaf.ro/api/PlatitorTvaRest/v9/tva
```

**Method:** POST

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
[
  {
    "cui": 17013137,
    "data": "2026-03-26"
  }
]
```

**Rate Limits:**
- Max 100 CUI-uri per request
- 1 request per secundă

---

## SOLR Company Core Schema

| Field | Description |
|-------|-------------|
| `id` | CIF (Company Unique Identifier) |
| `company` | Numele companiei (`denumire` din ANAF) |

---

## PHASE 1: Basic Company Update

### Goal
Adaugă/Actualizează în SOLR core-ul `company`:

### Data Mapping
| ANAF Field | SOLR Field |
|------------|------------|
| `cui` | `id` |
| `denumire` | `company` |

### Framework & Stack

| Component | Technology |
|-----------|------------|
| **Language** | PHP |
| **Container** | Docker |
| **Database** | Apache SOLR |
| **Config** | Environment variables |

#### Configuration (from `peviitor_core/opencode.json`)
```json
{
  "solr": {
    "url": "http://localhost:8983/solr",
    "cores": {
      "company": "http://localhost:8983/solr/company"
    },
    "auth": {
      "username": "solr",
      "password": "SolrRocks"
    }
  }
}
```

### Implementation Steps

1. **Preia toate CIF-urile** existente din core-ul `company` care au `company` gol sau NULL

   ```php
   // Query SOLR for companies without name
   $query = "company:* NOT company:\"\"";
   $solrUrl = getenv('SOLR_URL') ?: 'http://host.docker.internal:8983/solr/company/select';
   ```

2. **Interoghează ANAF** în batch-uri de max 100 CUI-uri (cu delay de 1 secundă între request-uri)

   ```php
   $anafUrl = "https://webservicesp.anaf.ro/api/PlatitorTvaRest/v9/tva";
   $batch = array_slice($cifs, $i, 100);
   // delay(1) - respectă rate limit
   ```

3. **Mapare date:**
   ```php
   $document = [
     'id'      => (string) $anafData['date_generale']['cui'],
     'company' => $anafData['date_generale']['denumire']
   ];
   ```

4. **Actualizează SOLR** - folosește endpoint-ul de update pentru a adăuga/modifica documentele

   ```php
   // POST to SOLR update endpoint
   $updateUrl = $solrUrl . '/update?commit=true';
   ```

### Success Criteria
- [ ] Se preiau toate CIF-urile din company core fără denumire
- [ ] Se interoghează ANAF cu batching corect (max 100, delay 1s)
- [ ] Se actualizează câmpul `company` în SOLR cu `denumire` din ANAF
- [ ] Se respectă configurația din `opencode.json` (URL, auth)

---

## PHASE 2: Company Status Update

### Goal
Actualizează câmpul `status` în SOLR pe baza stării din ANAF.

### Logic
- **Active**: `stare_inregistrare` conține "INREGISTRAT"
- **Inactive**: `stare_inregistrare` conține "radiat", "inactiv", etc.

> Doar companiile ACTIVE au șanse să aibă job-uri deschise pe peviitor.ro

### Implementation Steps

1. **Preia toate CIF-urile** din company core (filtrare: cele care au nevoie de actualizare status)

2. **Interoghează ANAF** - aceeași logică de batching

3. **Extrage status din ANAF:**
   ```php
   $stare = $anafData['date_generale']['stare_inregistrare'];
   $status = (strpos($stare, 'INREGISTRAT') !== false) ? 'activ' : 'inactiv';
   ```

4. **Actualizează SOLR:**
   ```php
   $document = [
     'id'     => (string) $cif,
     'status' => $status
   ];
   ```

### Success Criteria
- [ ] Se actualizează câmpul `status` pentru toate companiile
- [ ] Se identifică corect companiile active vs inactive

---

## PHASE 3: Location Update

### Goal
Actualizează câmpul `location` în SOLR pe baza adresei din ANAF.

### Data Mapping
| ANAF Field | SOLR Field |
|------------|------------|
| `adresa_sediu_social` | `location` |

### Implementation
- Se formează adresa completă din câmpurile ANAF: `sdenumire_Localitate`, `sdenumire_Judet`
- Exemplu: "Mun. Oradea, JUD. BIHOR"

---

## PHASE 4: Validation & Re-verification (Future)

### Goal
Re-verificare periodică a companiilor existente pentru a asigura date valide.

### Logic
1. **Re-verifică toate companiile** active din SOLR împotriva ANAF
2. **Actualizează status** dacă s-a schimbat (ex: activ → radiat)
3. **Loghează modificările** pentru audit

### Use Case
- O companie care era activă anul trecut poate fi acum radiată/inactivă
- Trebuie să actualizăm statusul pentru a nu afișa job-uri de la companii inexistente

### Success Criteria
- [ ] Se verifică periodic toate companiile active
- [ ] Se detectează schimbările de status
- [ ] Se loghează modificările

---

## PHASE 5: Website & Career Discovery (Future)

### Goal
Identifică automat website-ul și pagina de cariere ale companiei folosind AI și browsing automat.

### Important Context
> Aceeași denumire de companie poate exista în **3 judete diferite**. Locația și Județul sunt **foarte importante** pentru a identifica corect compania.

### Data Available
Din PHASE 1-3 avem:
- `company` - denumirea
- `location` - locația (include judetul)
- `adresa_sediu_social` - detalii adresă (judet, localitate)

### Tools

#### 1. Exa Web Search
Căutare web pentru găsirea website-ului oficial.

#### 2. Chrome DevTools (MCP)
- Port: `9222` (debug mode)
- Profil: **nou, fresh** (fără date anterioare)
- Mode: **headless**
- **Fără interactivitate** - nu întreabă de profil la pornire

### Implementation

1. **Construiește query-ul de căutare:**
   ```php
   // Exemplu: "SC IULIUS MALL SA" + "Oradea" + "Bihor"
   $query = $company . " " . $location;
   ```

2. **Exa Search** → găsește website oficial

3. **Chrome DevTools** → verifică website-ul găsit
   - Navighează la website
   - Caută pagină de cariere (/jobs, /careers, /romania, etc.)
   - Extrage URL-ul paginii de cariere

4. **Actualizează SOLR:**
   ```php
   $document = [
     'id'      => $cif,
     'website' => $websiteUrl,
     'career'  => $careerPageUrl
   ];
   ```

### Chrome Startup (headless, no profile prompt)
```bash
chrome --remote-debugging-port=9222 \
       --headless \
       --new-window \
       --user-data-dir=/tmp/chrome-fresh-profile \
       --no-first-run \
       --no-default-browser-check
```

### Success Criteria
- [ ] Se identifică website-ul companiei corecte (după denumire + judet)
- [ ] Se identifică pagina de cariere
- [ ] Chrome rulează headless fără interactivitate
- [ ] Se actualizează câmpurile `website` și `career` în SOLR

---

## PHASE 6: Brand Discovery (Future)

### Goal
Identifică brandurile asociate unei companii.

### Implementation
- Folosește OpenCode AI pentru a căuta online brandurile companiei
- Query: "[nume companie] brand" + locație
- Actualizează câmpul `brand` în SOLR

---

## PHASE 7: Group Discovery (Future)

### Goal
Identifică grupul din care face parte compania (holding, lanț, etc.).

### Implementation
- Folosește OpenCode AI pentru a căuta grupul companiei
- Query: "[nume companie] holding group" + locație
- Actualizează câmpul `group` în SOLR

---

## PHASE 8: Automation & Scheduling (Future)

### GitHub Actions Workflow

Job-ul va rula automat în GitHub Actions, zilnic la o oră stabilită.

```yaml
name: Update Companies from ANAF
on:
  schedule:
    - cron: '0 2 * * *'  # Zilnic la ora 02:00 UTC
  workflow_dispatch:      # Rulează manual

jobs:
  update-companies:
    runs-on: ubuntu-latest
    steps:
      - name: Run ANAF Company Updater
        run: |
          docker run --rm \
            -e SOLR_URL=${{ secrets.SOLR_URL }} \
            -e SOLR_USER=${{ secrets.SOLR_USER }} \
            -e SOLR_PASS=${{ secrets.SOLR_PASS }} \
            peviitor/anaf-company-updater
```

### Requirements
- [ ] Workflow GitHub Actions configurat
- [ ] Cron schedule: zilnic la ora stabilită (ex: 02:00 UTC)
- [ ] Secret-uri configurate în GitHub repo
- [ ] Permisiuni pentru access la container
