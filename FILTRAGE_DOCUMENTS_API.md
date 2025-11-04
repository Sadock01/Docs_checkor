# 📋 Documentation API - Filtrage des Documents

## 🔗 Endpoint

**Route :** `POST /api/documents/filter`  
**Méthode :** `POST`  
**Authentification :** Non requise (route publique)  
**Content-Type :** `application/json`

## 🔍 Vue d'ensemble

Cette API permet de filtrer et rechercher des documents selon de multiples critères combinables :

1. **Recherche par identifiant** : Recherche partielle sur l'identifiant du document
2. **Filtre par libellé du type** : Recherche par nom du type (ex: "Certificat", "Diplôme")
3. **Filtre par type_id** : Filtre exact par ID du type
4. **Filtre par période date_information** : Documents dont la date d'information est dans une plage
5. **Filtre par période de création** : Documents créés entre deux dates
6. **Recherche globale** : Recherche dans plusieurs champs simultanément

## 📤 Paramètres de requête

Tous les paramètres sont **optionnels** et peuvent être **combinés**.

### Paramètres de recherche

| Paramètre | Type | Format | Description | Exemple |
|-----------|------|--------|-------------|---------|
| `identifier` | string | texte | Recherche partielle sur l'identifiant | `"DOC-001"` |
| `type_name` | string | texte | Recherche partielle sur le nom du type | `"Certificat"` |
| `type_id` | integer | nombre | Filtre exact par ID du type | `5` |
| `search` | string | texte | Recherche globale (identifier, description, beneficiaire, type_name) | `"bénéficiaire"` |

### Paramètres de dates

| Paramètre | Type | Format | Description | Exemple |
|-----------|------|--------|-------------|---------|
| `date_information_start` | string | `YYYY-MM-DD` | Date de début pour date_information | `"2024-01-01"` |
| `date_information_end` | string | `YYYY-MM-DD` | Date de fin pour date_information | `"2024-12-31"` |
| `created_start` | string | `YYYY-MM-DD` ou `YYYY-MM-DD HH:mm:ss` | Date/heure de début de création | `"2024-01-01"` ou `"2024-01-01 00:00:00"` |
| `created_end` | string | `YYYY-MM-DD` ou `YYYY-MM-DD HH:mm:ss` | Date/heure de fin de création | `"2024-12-31"` ou `"2024-12-31 23:59:59"` |

### Paramètres de pagination

| Paramètre | Type | Défaut | Description |
|-----------|------|--------|-------------|
| `page` | integer | `1` | Numéro de page |
| `per_page` | integer | `10` | Nombre d'éléments par page |

## 📥 Format de requête

### Requête simple - Recherche par identifiant

```json
{
  "identifier": "DOC-001"
}
```

### Requête avec filtres multiples

```json
{
  "identifier": "DOC",
  "type_name": "Certificat",
  "created_start": "2024-01-01",
  "created_end": "2024-12-31",
  "date_information_start": "2024-06-01",
  "date_information_end": "2024-06-30",
  "page": 1,
  "per_page": 20
}
```

### Requête - Période de création uniquement

```json
{
  "created_start": "2024-01-01 00:00:00",
  "created_end": "2024-12-31 23:59:59"
}
```

### Requête - Recherche globale + Type

```json
{
  "search": "bénéficiaire",
  "type_id": 3
}
```

## 📤 Format de réponse

### Succès (200 OK)

```json
{
  "status_code": 200,
  "message": "Documents filtrés avec succès",
  "current_page": 1,
  "last_page": 5,
  "total": 47,
  "filters_applied": {
    "identifier": "DOC-001",
    "type_name": "Certificat",
    "type_id": null,
    "date_information_period": ["2024-06-01", "2024-06-30"],
    "created_period": ["2024-01-01", "2024-12-31"],
    "search": null
  },
  "data": [
    {
      "id": 1,
      "identifier": "DOC-001",
      "description": "Description du document",
      "beneficiaire": "John Doe",
      "date_information": "2024-06-15",
      "type_id": 5,
      "type_name": "Certificat",
      "created_at": "2024-06-15T10:30:00.000000Z"
    },
    // ... autres documents
  ]
}
```

### Erreur (500 Internal Server Error)

```json
{
  "status_code": 500,
  "message": "Erreur lors du filtrage des documents",
  "error": "Message d'erreur détaillé"
}
```

## 💻 Exemples d'implémentation

### JavaScript/TypeScript (Fetch API)

```typescript
interface FilterParams {
  identifier?: string;
  type_name?: string;
  type_id?: number;
  date_information_start?: string;
  date_information_end?: string;
  created_start?: string;
  created_end?: string;
  search?: string;
  page?: number;
  per_page?: number;
}

async function filterDocuments(filters: FilterParams) {
  try {
    const response = await fetch('/api/documents/filter', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(filters)
    });

    if (!response.ok) {
      throw new Error('Erreur lors du filtrage');
    }

    const data = await response.json();
    
    return {
      documents: data.data,
      pagination: {
        currentPage: data.current_page,
        lastPage: data.last_page,
        total: data.total
      },
      filtersApplied: data.filters_applied
    };
  } catch (error) {
    console.error('Erreur:', error);
    throw error;
  }
}

// Utilisation
const results = await filterDocuments({
  identifier: 'DOC-001',
  type_name: 'Certificat',
  created_start: '2024-01-01',
  created_end: '2024-12-31',
  page: 1,
  per_page: 20
});
```

### React avec Axios

```typescript
import axios from 'axios';

interface Document {
  id: number;
  identifier: string;
  description: string;
  beneficiaire: string;
  date_information: string;
  type_id: number;
  type_name: string;
  created_at: string;
}

interface FilterResponse {
  status_code: number;
  message: string;
  current_page: number;
  last_page: number;
  total: number;
  filters_applied: Record<string, any>;
  data: Document[];
}

const useFilterDocuments = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [results, setResults] = useState<FilterResponse | null>(null);

  const filterDocuments = async (filters: Record<string, any>) => {
    setLoading(true);
    setError(null);

    try {
      const response = await axios.post<FilterResponse>(
        '/api/documents/filter',
        filters,
        {
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        }
      );

      setResults(response.data);
      return response.data;
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 'Erreur lors du filtrage';
      setError(errorMessage);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  return { filterDocuments, loading, error, results };
};

// Utilisation dans un composant
function DocumentFilter() {
  const { filterDocuments, loading, error, results } = useFilterDocuments();
  const [filters, setFilters] = useState({
    identifier: '',
    type_name: '',
    created_start: '',
    created_end: '',
    date_information_start: '',
    date_information_end: '',
    type_id: null as number | null,
    page: 1,
    per_page: 10
  });

  const handleFilter = async () => {
    // Nettoyer les valeurs vides avant d'envoyer
    const cleanFilters = Object.fromEntries(
      Object.entries(filters).filter(([_, value]) => 
        value !== '' && value !== null && value !== undefined
      )
    );

    await filterDocuments(cleanFilters);
  };

  return (
    <div>
      <input
        placeholder="Identifiant"
        value={filters.identifier}
        onChange={(e) => setFilters({ ...filters, identifier: e.target.value })}
      />
      <input
        placeholder="Nom du type"
        value={filters.type_name}
        onChange={(e) => setFilters({ ...filters, type_name: e.target.value })}
      />
      <input
        type="date"
        placeholder="Date création début"
        value={filters.created_start}
        onChange={(e) => setFilters({ ...filters, created_start: e.target.value })}
      />
      <input
        type="date"
        placeholder="Date création fin"
        value={filters.created_end}
        onChange={(e) => setFilters({ ...filters, created_end: e.target.value })}
      />
      
      <button onClick={handleFilter} disabled={loading}>
        {loading ? 'Recherche...' : 'Filtrer'}
      </button>

      {error && <div className="error">{error}</div>}

      {results && (
        <div>
          <p>Total: {results.total} documents</p>
          <p>Page {results.current_page} sur {results.last_page}</p>
          {results.data.map((doc) => (
            <div key={doc.id}>
              <h3>{doc.identifier}</h3>
              <p>{doc.type_name}</p>
              <p>{doc.beneficiaire}</p>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
```

### React Query (TanStack Query)

```typescript
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';

const useFilterDocuments = (filters: Record<string, any>) => {
  return useQuery({
    queryKey: ['documents', 'filter', filters],
    queryFn: async () => {
      // Nettoyer les valeurs vides
      const cleanFilters = Object.fromEntries(
        Object.entries(filters).filter(([_, value]) => 
          value !== '' && value !== null && value !== undefined
        )
      );

      const { data } = await axios.post('/api/documents/filter', cleanFilters);
      return data;
    },
    enabled: Object.values(filters).some(v => v !== '' && v !== null && v !== undefined),
    staleTime: 30000, // 30 secondes
  });
};

// Utilisation
function DocumentsList() {
  const [filters, setFilters] = useState({});
  const { data, isLoading, error } = useFilterDocuments(filters);

  if (isLoading) return <div>Chargement...</div>;
  if (error) return <div>Erreur: {error.message}</div>;

  return (
    <div>
      {/* Formulaire de filtres */}
      <FilterForm onFilter={setFilters} />
      
      {/* Liste des documents */}
      {data?.data.map(doc => (
        <DocumentCard key={doc.id} document={doc} />
      ))}
      
      {/* Pagination */}
      <Pagination 
        current={data?.current_page}
        total={data?.last_page}
        onChange={(page) => setFilters({ ...filters, page })}
      />
    </div>
  );
}
```

## 🎯 Cas d'usage courants

### 1. Recherche par identifiant exact ou partiel

```typescript
// Trouver tous les documents avec "DOC" dans l'identifiant
filterDocuments({ identifier: 'DOC' });
```

### 2. Filtrer par type (par nom)

```typescript
// Trouver tous les "Certificats"
filterDocuments({ type_name: 'Certificat' });
```

### 3. Documents créés ce mois

```typescript
const today = new Date();
const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);

filterDocuments({
  created_start: firstDay.toISOString().split('T')[0],
  created_end: lastDay.toISOString().split('T')[0]
});
```

### 4. Combinaison : Type + Période + Identifiant

```typescript
filterDocuments({
  type_id: 5,
  created_start: '2024-01-01',
  created_end: '2024-12-31',
  identifier: 'DOC'
});
```

### 5. Recherche globale avec pagination

```typescript
filterDocuments({
  search: 'bénéficiaire',
  page: 2,
  per_page: 20
});
```

## 📝 Notes importantes

### Dates

1. **Format des dates** : 
   - `date_information` : Format `YYYY-MM-DD` (ex: `2024-06-15`)
   - `created_at` : Format `YYYY-MM-DD` ou `YYYY-MM-DD HH:mm:ss`

2. **Dates partielles** : 
   - Vous pouvez fournir seulement `date_information_start` (toutes les dates >= cette date)
   - Vous pouvez fournir seulement `date_information_end` (toutes les dates <= cette date)
   - Même logique pour `created_start` et `created_end`

3. **Date de fin** : 
   - La date de fin inclut automatiquement toute la journée (jusqu'à 23:59:59)

### Recherche

1. **Recherche partielle** : 
   - `identifier`, `type_name`, et `search` utilisent une recherche LIKE (partielle)
   - `type_id` est une correspondance exacte

2. **Combinaison de filtres** : 
   - Tous les filtres s'appliquent avec **ET** (AND)
   - Si vous combinez `identifier` et `type_name`, il trouvera les documents qui correspondent aux deux

3. **Recherche globale (`search`)** : 
   - Recherche dans : identifier, description, beneficiaire, type_name
   - Peut être combinée avec d'autres filtres spécifiques

### Pagination

1. **Par défaut** : 10 éléments par page, page 1
2. **Total** : Le champ `total` indique le nombre total de documents correspondant aux filtres
3. **Navigation** : Utiliser `current_page` et `last_page` pour la pagination

## 🎨 Suggestions d'UI

### Formulaire de filtres

```
┌─────────────────────────────────────┐
│  Filtres de recherche               │
├─────────────────────────────────────┤
│  Identifiant: [___________]          │
│  Type (nom):  [___________]         │
│  Type (ID):   [__]                  │
│                                     │
│  Date information:                  │
│    Du: [📅______] Au: [📅______]     │
│                                     │
│  Date de création:                  │
│    Du: [📅______] Au: [📅______]     │
│                                     │
│  Recherche globale: [___________]    │
│                                     │
│  [Filtrer] [Réinitialiser]          │
└─────────────────────────────────────┘
```

### Affichage des résultats

```
┌─────────────────────────────────────┐
│  Résultats (47 documents)           │
│  Page 1 sur 5                        │
├─────────────────────────────────────┤
│  DOC-001 - Certificat               │
│  Bénéficiaire: John Doe             │
│  Date: 2024-06-15                   │
├─────────────────────────────────────┤
│  DOC-002 - Diplôme                  │
│  Bénéficiaire: Jane Smith           │
│  Date: 2024-06-16                   │
└─────────────────────────────────────┘
  [< Précédent] 1 2 3 4 5 [Suivant >]
```

## ✅ Checklist d'implémentation

- [ ] Créer le formulaire de filtres avec tous les champs
- [ ] Gérer les dates avec des inputs `type="date"`
- [ ] Nettoyer les valeurs vides avant d'envoyer la requête
- [ ] Afficher les états de chargement
- [ ] Gérer les erreurs (réseau, serveur)
- [ ] Afficher les résultats avec pagination
- [ ] Permettre de réinitialiser les filtres
- [ ] Mettre en évidence les filtres actifs
- [ ] Sauvegarder les filtres dans l'URL (query params) pour le partage
- [ ] Débouncer les recherches textuelles (pour éviter trop de requêtes)

