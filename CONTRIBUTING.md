# Documentation de contribution

## Règles générales

- Assurez-vous qu'il n'y a pas de demandes de PR existantes tentants de résoudre le problème mentionné;
- Les changements importants doivent être discutés dans le ticket;
- Développez dans une branche spécifique, pas sur la branche principale (2.5);
- Donnez un titre à votre PR qui respecte la spécification [conventional commits](https://www.conventionalcommits.org/en/v1.0.0/);
- Donnez une description à votre PR et expliquez pourquoi nous devrions la merger;

## Conventions

- Ajouter autant que possible des tests;
> Les actions ci-dessous nécessitent de disposer de PHP en version 8.1. Un exemple d'utilisation des commandes est disponible dans la section **scripts** du fichier _composer.json_.
- Exécuter Rector;
- Exécuter PHP-CS-Fixer;
- Exécuter PHPStan et ne pas ajouter d'erreur (dans la mesure du possible). Si la correction de l'erreur est trop complexe, l'ajouter dans la baseline;