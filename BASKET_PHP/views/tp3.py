{
  "cells": [
    {
      "cell_type": "code",
      "execution_count": 1,
      "id": "d8ed4242-97ba-48dd-93c9-808630e10ed2",
      "metadata": {

      },
      "outputs": [],
      "source": [
        "import numpy as np\n",
        "import sympy as sy"
      ]
    },
    {
      "cell_type": "code",
      "execution_count": 2,
      "id": "517c6ff3-5193-4b7b-b4e7-71a629483497",
      "metadata": {

      },
      "outputs": [],
      "source": [
        "M1 = np.array([[0.2,0.2,0.6],\n",
        "               [0.5,0.1,0.4],\n",
        "               [0.3,0.7,0.0]\n",
        "              ])\n",
        "M2 = np.array([[0.2,0.2,0.6,0.0,0.0,0.0,0.0,0.0],\n",
        "               [0.5,0.1,0.4,0.0,0.0,0.0,0.0,0.0],\n",
        "               [0.3,0.7,0.0,0.0,0.0,0.0,0.0,0.0],\n",
        "               [0.0,0.0,0.0,0.5,0.5,0.0,0.0,0.0],\n",
        "               [0.0,0.0,0.0,0.3,0.7,0.0,0.0,0.0],\n",
        "               [0.0,0.0,0.0,0.0,0.0,0.2,0.7,0.1],\n",
        "               [0.0,0.0,0.0,0.0,0.0,0.4,0.0,0.6],\n",
        "               [0.0,0.0,0.0,0.0,0.0,0.5,0.2,0.3]\n",
        "              ])\n",
        "mu_0 = np.array([[1],[0],[0],[0],[0],[0],[0],[0]])"
      ]
    },
    {
      "cell_type": "code",
      "execution_count": 4,
      "id": "287bd89a-4b09-4b2a-9a5b-cf2624052f77",
      "metadata": {

      },
      "outputs": [],
      "source": [
        "def gauss_jordan_exact(A):\n",
        "    # Simplification exacte des éléments de la matrice A\n",
        "    A = np.array([[sy.nsimplify(A[i, j]) for j in range(len(A[i]))] for i in range(len(A))])\n",
        "\n",
        "    # Boucle principale pour effectuer l'algorithme de Gauss-Jordan\n",
        "    for i in range(len(A)):\n",
        "        # Échange de lignes si l'élément diagonal est nul\n",
        "        if A[i, i] == 0:\n",
        "            # Chercher une ligne non nulle en dessous\n",
        "            j = i + 1\n",
        "            # Si une ligne non nulle est trouvée en dessous\n",
        "            while (j \u003C len(A)) and (A[j, i] == 0):\n",
        "                j += 1\n",
        "                 # Ajouter la ligne trouvée à la ligne actuelle pour éviter le 0 sur la diagonale            if j \u003C len(A):\n",
        "                A[i, :] = A[i, :] + A[j, :]\n",
        "            else:\n",
        "                continue\n",
        "\n",
        "        # Normalisation de la ligne pour que l'élément diagonal soit égal à 1\n",
        "        c = A[i, i]\n",
        "        A[i, :] = A[i, :] / c\n",
        "\n",
        "        # Élimination des autres éléments dans la colonne\n",
        "        for j in range(len(A)):\n",
        "            if j != i:\n",
        "                c = -A[j, i] / A[i, i] # le coef qui annule le coeff de la ligne j dans la colonne i\n",
        "                A[j, :] = A[j, :] + c * A[i, :] # On ajoute a la ligne j la ligne i * par le coeff\n",
        "\n",
        "    # Retourne la matrice résultante après l'algorithme de Gauss-Jordan\n",
        "    return A\n",
        "\n",
        "\n",
        "def mesure_in_list(mu,l,ecart=0.01):\n",
        "    \"\"\"Détermine si une mesure appartient à un liste avec une tolérance de écart.\"\"\"\n",
        "    dedans = False\n",
        "    for j in range(len(l)):\n",
        "        dedans = dedans | np.isclose(l[j],mu,atol=ecart).all()\n",
        "    return dedans\n"
      ]
    },
    {
      "cell_type": "code",
      "execution_count": null,
      "id": "fee10ff4-f5c6-47bc-a0bd-71b2e3837632",
      "metadata": {

      },
      "outputs": [],
      "source": []
    }
  ],
  "metadata": {
    "kernelspec": {
      "display_name": "Python 3 (ipykernel)",
      "language": "python",
      "name": "python3"
    },
    "language_info": {
      "codemirror_mode": {
        "name": "ipython",
        "version": 3
      },
      "file_extension": ".py",
      "mimetype": "text/x-python",
      "name": "python",
      "nbconvert_exporter": "python",
      "pygments_lexer": "ipython3",
      "version": "3.12.4"
    }
  },
  "nbformat": 4,
  "nbformat_minor": 5
}