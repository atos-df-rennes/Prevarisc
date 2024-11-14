![](https://github.com/atos-df-rennes/prevarisc/actions/workflows/actions.yml/badge.svg?event=push)

# Prevarisc

[Prevarisc](http://sdis62.github.io/prevarisc/) - Application web open-source gérant l'ensemble de l'activité du service prévention au sein d'un Service Départemental d'Incendie et de Secours (SDIS).
> Fork maintenu de [SDIS62/prevarisc](https://github.com/SDIS62/prevarisc)

![](http://sdis62.github.io/prevarisc/assets/img/screenshot.png)

## Table des matières

- [Fonctionnalités](#fonctionnalités)
- [Technologies utilisées](#technologies-utilisées)
- [Installation](#installation)
- [Suivi du projet](#suivi-du-projet)
- [Contribuer](#contribuer)
- [Licence](#licence)

## Fonctionnalités

- **Géolocalisation des établissements** : Les établissements sont géolocalisés automatiquement à la saisie et sont visibles sur une carte [Géoplateforme](https://www.ign.fr/geoplateforme).
- **Intégration LDAP** : Prevarisc permet d'interagir  avec votre annuaire d'entreprise. Plus besoin de créer de double compte, utilisez simplement l'existant !
- **Génération automatique de documents ODT** : En un clic, générez des documents d'études et/ou de visites.
- **Gestion des commissions** : Consultez les calendriers des commissions, gérez le passage des dossiers en salle.
- ~~**Extractions et statistiques** : Exploitez la base de données de prevarisc pour sortir des statistiques aidant notamment à remplir INFOSDIS.~~
- **Open Source** : Prevarisc est en constante évolution ! Simple utilisateur ou contributeur, participez à son amélioration.

## Technologies utilisées

- **PHP** : Utilisé avec le framework [Zendframework 1](https://framework.zend.com/manual/1.12/en/manual.html)
- **JavaScript** : Utilisé avec [jQuery](https://jquery.com/)
- **Bootstrap** : Utilisé en [version 2](https://getbootstrap.com/2.3.2/)

## Installation

Pour installer Prevarisc sur un serveur web, suivez les étapes ci-dessous :

1. Clonez le dépôt :
```sh
$ git clone https://github.com/atos-df-rennes/prevarisc.git
```
2. Accédez au répertoire du projet :
```sh
$ cd prevarisc
```
3. Installez les dépendances :
```sh
$ composer install --prefer-source
```
4. Appliquez les permissions nécessaires :
```sh
$ chown –R www-data:www-data *
$ chmod –R 555 *
$ chmod –R 755 public/
```
Ensuite vous pouvez créer un fichier secret.ini dans application/configs afin de configurer Prevarisc pour qu'il ait accès à la base de données (entre autres).
Vous devez créer un Virtualhost pointant vers le dossier public de Prevarisc.
Une documentation détaillée est disponible ici : [Documentation d'installation](docs/documentation_installation.md).

## Suivi du projet

* [Travail en cours / jalons](https://github.com/atos-df-rennes/prevarisc/milestones);
* [Suivi des bugs et corrections](https://github.com/atos-df-rennes/prevarisc/issues);

## Contribuer

Pour contribuer à cette version de Prevarisc, consultez la [documentation de contribution](CONTRIBUTING.md).

> En plus de la [procédure d'installation](#installation) standard, il vous faudra installer les dépendances de développement avec une version 8.1 de PHP :
> ```sh
>     php8.1 /usr/local/bin/composer install --working-dir=tools
> ```

Sinon, Prevarisc est une application fork-friendly et vous pouvez parfaitement maintenir une version personnalisée.

## Licence

Ce projet est sous license [CeCILL-B](http://www.cecill.info/licences/Licence_CeCILL-B_V1-fr.html). Consultez le fichier [LICENSE](LICENSE.md) pour plus de détails.
