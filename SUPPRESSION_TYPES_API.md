# 📋 Documentation API - Suppression des Types

## 🔗 Endpoint

**Route :** `DELETE /api/types/{id}`  
**Méthode :** `DELETE`  
**Authentification :** Requise (Bearer Token via Sanctum)

## 🔍 Logique de suppression

La suppression d'un type fonctionne de la manière suivante :

1. **Vérification d'existence** : Le système vérifie si le type existe
2. **Comptage des documents** : Le système compte dynamiquement le nombre de documents qui utilisent ce type
3. **Décision** :
   - Si `nombre_documents = 0` → **Suppression autorisée** (soft delete)
   - Si `nombre_documents > 0` → **Suppression refusée** avec message d'erreur

## 📤 Réponses possibles

### ✅ Succès - Type supprimé

**Code HTTP :** `200 OK`

```json
{
  "status_code": 200,
  "message": "Le type a été supprimé (soft delete) avec succès."
}
```

**Quand ?** Quand le type n'est utilisé par aucun document.

---

### ❌ Erreur - Type utilisé par des documents

**Code HTTP :** `409 Conflict`

```json
{
  "status_code": 409,
  "message": "Impossible de supprimer ce type. Il est rattaché à 5 document(s).",
  "nombre_documents": 5
}
```

**Quand ?** Quand le type est utilisé par un ou plusieurs documents.

**Actions recommandées pour le frontend :**
- Afficher un message d'erreur avec le nombre exact de documents
- Proposer de modifier ou supprimer d'abord les documents concernés
- Peut-être proposer un remplacement du type sur les documents existants

---

### ❌ Erreur - Type introuvable

**Code HTTP :** `404 Not Found`

```json
{
  "status_code": 404,
  "message": "Type introuvable."
}
```

**Quand ?** Quand l'ID du type n'existe pas dans la base de données.

---

### ❌ Erreur - Erreur serveur

**Code HTTP :** `500 Internal Server Error`

```json
{
  "status_code": 500,
  "message": "Une erreur est survenue lors de la suppression du type.",
  "error": "Message d'erreur technique détaillé"
}
```

**Quand ?** En cas d'erreur inattendue (base de données, etc.).

---

## 💻 Exemple d'implémentation frontend

### JavaScript/TypeScript (Axios)

```typescript
async function deleteType(typeId: number) {
  try {
    const response = await axios.delete(
      `/api/types/${typeId}`,
      {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      }
    );

    // Succès (200)
    if (response.status === 200) {
      showSuccess('Type supprimé avec succès');
      // Rafraîchir la liste des types
      refreshTypesList();
    }

  } catch (error: any) {
    const status = error.response?.status;
    const data = error.response?.data;

    switch (status) {
      case 409: // Conflict - Type utilisé
        showError(
          `Impossible de supprimer ce type. ${data.message}\n` +
          `Il est utilisé par ${data.nombre_documents} document(s).`
        );
        // Optionnel : Afficher un modal avec les documents concernés
        break;

      case 404: // Not Found
        showError('Ce type n\'existe plus.');
        break;

      case 500: // Server Error
        showError('Erreur serveur : ' + data.message);
        break;

      default:
        showError('Erreur lors de la suppression du type.');
    }
  }
}
```

### React avec React Query

```typescript
import { useMutation, useQueryClient } from '@tanstack/react-query';

const useDeleteType = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (typeId: number) => {
      const response = await fetch(`/api/types/${typeId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      });

      const data = await response.json();

      if (!response.ok) {
        throw { status: response.status, data };
      }

      return data;
    },
    onSuccess: () => {
      // Invalider et refetch la liste des types
      queryClient.invalidateQueries({ queryKey: ['types'] });
    },
    onError: (error: any) => {
      if (error.status === 409) {
        toast.error(
          `Impossible de supprimer. ${error.data.message} ` +
          `(${error.data.nombre_documents} document(s))`
        );
      } else {
        toast.error('Erreur lors de la suppression');
      }
    }
  });
};

// Utilisation dans un composant
function TypeDeleteButton({ typeId }: { typeId: number }) {
  const deleteType = useDeleteType();

  const handleDelete = () => {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce type ?')) {
      deleteType.mutate(typeId);
    }
  };

  return (
    <button onClick={handleDelete} disabled={deleteType.isPending}>
      {deleteType.isPending ? 'Suppression...' : 'Supprimer'}
    </button>
  );
}
```

---

## 📝 Notes importantes

1. **Soft Delete** : La suppression est un "soft delete", ce qui signifie que le type est marqué comme supprimé mais peut être restauré si nécessaire.

2. **Comptage dynamique** : Le nombre de documents est calculé à chaque requête, pas stocké dans un attribut `is_used`. C'est plus fiable et évite les problèmes de synchronisation.

3. **Authentification** : N'oubliez pas d'inclure le token Bearer dans les headers de la requête.

4. **Route** : ✅ La route DELETE est configurée dans `routes/api.php` :
   ```php
   Route::delete('types/{id}', [TypeController::class, 'destroy']);
   ```

---

## 🎯 Checklist pour le frontend

- [ ] Afficher un message de confirmation avant suppression
- [ ] Gérer le cas 409 (type utilisé) avec message clair
- [ ] Afficher le nombre exact de documents qui utilisent le type
- [ ] Rafraîchir la liste après suppression réussie
- [ ] Gérer les états de chargement (pending)
- [ ] Gérer les erreurs réseau
- [ ] Afficher un message de succès après suppression

