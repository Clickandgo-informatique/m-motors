# Implémentation et Validation des Tests

## Projet de Fin d'Études

**Étudiant :**  
Leveque Emmanuel

**Promotion :**

**Formation :**  
Concepteur d'application / Chef de projet Web

**Encadrant :**  
Ala Atrash

**Année universitaire :**  
2025 - 2026
(Session juin 2026)


---

# Table des matières

1. Introduction  
2. Objectifs des tests  
3. Technologies utilisées  
4. Stratégie de tests  
5. Mise en place des tests unitaires  
6. Exécution des tests  
7. Génération de rapports de tests  
8. Justification des choix techniques  
9. Conclusion  

---

# 1. Introduction

Dans le cadre de ce projet de fin d'études, une attention particulière a été portée à la qualité du code à travers la mise en place d'une stratégie de tests automatisés.

Les tests permettent de vérifier le bon fonctionnement des différentes fonctionnalités du projet et d'assurer la stabilité de l'application lors des évolutions du code.

Ils constituent également une bonne pratique de développement permettant de détecter rapidement les anomalies et de garantir la robustesse de l'application.

---

# 2. Objectifs des tests

Les objectifs principaux sont :

- vérifier le comportement des services développés
- garantir la non-régression lors des modifications du code
- automatiser la validation des fonctionnalités
- améliorer la maintenabilité du projet
- assurer la fiabilité de l'application

---

# 3. Technologies utilisées

Les outils suivants ont été utilisés pour la mise en place des tests :

| Outil | Rôle |
|------|------|
| PHPUnit | Framework de tests unitaires pour PHP |
| Composer | Gestion des dépendances |
| Cypress (envisagé) | Tests end-to-end et génération de rapports |
| Git | Gestion de version |

PHPUnit constitue l'outil principal utilisé pour la création et l'exécution des tests unitaires dans ce projet.

---

# 4. Stratégie de tests

Dans le développement logiciel moderne, plusieurs niveaux de tests peuvent être mis en place afin de garantir la qualité d'une application.

Ces différents niveaux peuvent être représentés sous la forme d'une pyramide de tests :

        +----------------------+
        |   Tests End-to-End   |
        |      (Cypress)       |
        +----------▲-----------+
                   |
        +----------+-----------+
        |  Tests fonctionnels  |
        |   (API / intégration)|
        +----------▲-----------+
                   |
        +----------+-----------+
        |    Tests unitaires   |
        |      (PHPUnit)       |
        +----------------------+


Les **tests unitaires** constituent la base de cette pyramide et permettent de vérifier le comportement des composants individuels de l'application.

Les **tests fonctionnels** permettent de tester l'interaction entre plusieurs composants.

Enfin, les **tests end-to-end** simulent l'utilisation réelle de l'application par un utilisateur.

Dans ce projet, l'accent a été principalement mis sur les **tests unitaires et d'intégration**.

---

# 5. Mise en place des tests unitaires

Les tests unitaires ont été implémentés à l'aide du framework PHPUnit.

Chaque classe importante du projet possède une classe de test correspondante.

Par exemple :

Ce test vérifie le bon fonctionnement du service responsable du calcul des prix.

---

# Exemple de test unitaire

Ci-dessous un exemple simplifié d'un test PHPUnit :

```php
use PHPUnit\Framework\TestCase;
use App\Service\PriceCalculator;

class PriceCalculatorTest extends TestCase
{
    public function testCalculatePrice()
    {
        $calculator = new PriceCalculator();

        $result = $calculator->calculate(100, 0.2);

        $this->assertEquals(120, $result);
    }
}

Dans cet exemple :

une instance du service est créée

la méthode à tester est appelée

une assertion vérifie que le résultat correspond à la valeur attendue

La structure du dossier `tests` du projet est organisée de la manière suivante :

tests
├── Functional
│ ├── Controller
│ └── Security
├── Integration
│ ├── Repository
│ └── Service
│ └── PriceCalculatorTest.php
├── Unit
│ ├── Entity
│ ├── SanityTest.php
│ ├── Service
│ └── Util
└── bootstrap.php

Cette organisation permet de séparer les différents types de tests afin d'améliorer la lisibilité et la maintenabilité du projet.

- **Unit** : contient les tests unitaires des classes du projet  
- **Integration** : contient les tests vérifiant l'interaction entre plusieurs composants  
- **Functional** : contient les tests liés au comportement fonctionnel de l'application  

Chaque service ou composant important possède une classe de test correspondante.

Par exemple :

tests/Integration/Service/PriceCalculatorTest.php


Ces tests permettent de vérifier que les méthodes des services retournent les résultats attendus.

---

# 6. Exécution des tests

Les tests peuvent être exécutés via la commande suivante :

vendor/bin/phpunit

Exemple de résultat obtenu :

OK (2 tests, 2 assertions)


Ce résultat signifie que :

- **2 tests ont été exécutés**
- **2 assertions ont été validées**

Une assertion correspond à une vérification effectuée dans un test afin de s'assurer qu'une valeur correspond au résultat attendu.

---

# 7. Génération de rapports de tests

Afin d'améliorer la lisibilité des résultats et de faciliter l'analyse des tests, il est possible de générer des rapports de tests.

## PHPUnit

PHPUnit permet de produire différents types de rapports :

- affichage détaillé des tests
- rapport HTML
- analyse de la couverture de code

Exemple d'exécution avec affichage amélioré :

vendor/bin/phpunit --testdox


Ce mode permet d'obtenir une sortie plus lisible présentant les tests sous forme de descriptions.

## Cypress

L'utilisation de Cypress est envisagée afin de compléter les tests existants.

Cet outil permet de :

- réaliser des **tests end-to-end**
- simuler le comportement d'un utilisateur
- visualiser l'exécution des tests
- générer des **rapports détaillés**

Cypress est particulièrement adapté pour tester les interactions complètes avec l'application.

---

# 8. Justification des choix techniques

Le choix de PHPUnit s'explique par plusieurs facteurs :

- il s'agit de l'outil de référence pour les tests dans l'écosystème PHP
- il s'intègre facilement avec Composer
- il possède une documentation très complète
- il permet l'automatisation des tests dans un environnement de développement ou d'intégration continue

De plus, PHPUnit permet d'organiser les tests de manière claire et de produire différents types de rapports.

L'outil Cypress est envisagé afin de compléter les tests unitaires par des **tests end-to-end**, permettant de tester le comportement global de l'application dans des conditions proches de l'utilisation réelle.

---

# 9. Conclusion

La mise en place des tests automatisés constitue une étape essentielle dans le développement de ce projet.

Les tests permettent :

- d'améliorer la fiabilité du code
- de détecter rapidement les erreurs
- de sécuriser les évolutions du projet
- d'assurer une meilleure qualité logicielle

L'utilisation de PHPUnit a permis de structurer efficacement les tests du projet et d'automatiser leur exécution.

À terme, l'ajout de tests end-to-end permettra de compléter cette stratégie afin de couvrir l'ensemble du comportement de l'application.






