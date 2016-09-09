# Migration vers Webmecanik Automation v2.0
Attention, pour les utilisateurs de l'API lead et list, ces mises à jour sont obligatoires. 

## Mise à jour de l'API library
Téléchargez la nouvelle version de l'API Webmecanik Automation : https://github.com/Webmecanik/api-library

## Evolutions non-rétrocompatible
### leadApi
#### Anciennes commandes non supportées en 2.0
```
$leadApi = $this->getContext('leads');
$lists = $leadApi->getLists();
$lists ['leads']
//
$lists = $leadApi->getLists();
//
$leads = $leadApi->getLeadLists(1);
```

#### Commandes de remplacement
```
$contactApi = $this->getContext('contacts');
$lists = $contactApi->getLists();
$lists ['contacts']
//
$lists   = $contactApi->getSegments();
//
$contacts   = $contactApi->getContactSegments(1);
```

### listApi
#### Ancienne commande non supportée en 2.0
```
$listApi = $this->getContext('lists');
```
#### Commande de remplacement
```
$segmentApi = $this->getContext('segments');
```
