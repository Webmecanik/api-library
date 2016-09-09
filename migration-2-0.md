# Migration vers Webmecanik Automation v2.0
Attention, pour les utilisateurs de l'API lead et list, ces mises à jour sont obligatoires. 

## Mise à jour de l'API library
Téléchargez la nouvelle version de l'API Webmecanik Automation : https://github.com/Webmecanik/api-library

## Evolutions non-rétrocompatible
### leadApi
#### Anciennes fonctions non supportées en 2.0 et fonctions de remplacement

* `$leadApi = $this->getContext('leads');` --> `$contactApi = $this->getContext('contacts');`
* `$lists = $leadApi->getLists();` --> `$lists = $contactApi->getLists();`
* `$lists ['leads'];` --> `$lists ['contacts'];`
* `$lists = $leadApi->getLists();` --> `$lists   = $contactApi->getSegments();`
* `$leads = $leadApi->getLeadLists(1);` --> `$contacts   = $contactApi->getContactSegments(1);`

### listApi
#### Anciennes fonctions non supportées en 2.0 et fonctions de remplacement

* `$listApi = $this->getContext('lists');` --> `$segmentApi = $this->getContext('segments');`
