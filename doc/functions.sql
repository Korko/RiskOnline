delimiter //

#/* Fonction qui lance un de de type n, qui renvoie un entier entre 1 et n */
DROP FUNCTION IF EXISTS FUNC_ROLL_DICE//

CREATE FUNCTION FUNC_ROLL_DICE(diceType INT) RETURNS INT
BEGIN
	RETURN(FLOOR(1  + RAND() * (diceType-1)));
END//

#/* Fonction "lancer un de pour x troupes", cette couche permet de moduler le type du de en fonction du nombre de troupes */
DROP FUNCTION IF EXISTS FUNC_ROLL_DICE_FOR_TROOPS//

CREATE FUNCTION FUNC_ROLL_DICE_FOR_TROOPS(var_troop_qty INT) RETURNS INT
BEGIN
	RETURN(FUNC_ROLL_DICE(var_troop_qty));
END//

#/* Cette fonction declare la partie terminee. On l'historise et la supprime des parties actives */
DROP PROCEDURE IF EXISTS PROC_ENDGAME//

CREATE PROCEDURE PROC_ENDGAME(IN par_game_id INT(8), IN par_vict_id INT(8))
BEGIN
	#/* La partie est historisee */
	INSERT INTO mvc_games_history(g_id, g_start) 
		SELECT G.g_id, G.g_start 
		FROM mvc_games G 
		WHERE G.g_id=par_game_id;
		
	#/* On note son vainqueur (separe pour cause de non-autorisation d'acces a la variable par_vict_id comme retour du select) */
	UPDATE mvc_games_history
	SET victor_id=par_vict_id 
	WHERE g_id=par_game_id;
	
	#/* On la supprime des parties actives */
	DELETE FROM mvc_games
	WHERE g_id=par_game_id;
	
END//

/* Cette procedure donne une carte a un joueur precis */
DROP PROCEDURE IF EXISTS PROC_GIVE_CARD//

CREATE PROCEDURE PROC_GIVE_CARD(IN par_game_id INT(8), IN conqueror_id INT(8))
BEGIN
END//

#/* Cette procedure enregistre la conquete d'un territoire par un joueur, et y met les troupes */
DROP PROCEDURE IF EXISTS PROC_DECLARE_CONQUEST//

CREATE PROCEDURE PROC_DECLARE_CONQUEST(IN par_game_id INT(8), IN par_vict_id INT(8), IN par_cou_id INT(8), IN par_troops INT(8))
BEGIN
	
	UPDATE mvc_lands L
	SET L.m_id=par_vict_id, L.l_strength=par_troops
	WHERE L.g_id=par_game_id
	AND L.cou_id=par_cou_id;
	
	DELETE FROM mvc_actions_stack
	WHERE g_id=par_game_id
	AND cou_to=par_cou_id
	AND m_id=par_vict_id;
	
	CALL PROC_GIVE_CARD(par_game_id, par_vict_id);
END//

#/* Cette procedure resout les attaques croisees (A attaque B et B attaque A) en jouant le combat jusqu'a elimination d'une des deux armees */
DROP PROCEDURE IF EXISTS PROC_SOLVE_2WAY_ATTACKS//

CREATE PROCEDURE PROC_SOLVE_2WAY_ATTACKS(IN par_game_id INT(8))
BEGIN
	DECLARE dice1 INT;
	DECLARE dice2 INT;
	DECLARE troops1 INT;
	DECLARE land1 INT;
	DECLARE land2 INT;
	DECLARE troops2 INT;
	DECLARE done INT DEFAULT 0;
	#/* Curseur : l'ensemble des attaques croisees, avec le nombre de troupes de chaque armee */
	DECLARE cur_2way CURSOR FOR SELECT A1.cou_from, A1.cou_to, A1.a_strength, A2.a_strength
				FROM mvc_actions A1, mvc_actions A2
				WHERE A1.g_id=par_game_id
				AND A2.g_id=par_game_id
				AND A2.cou_from=A1.cou_to
				AND A2.cou_to=A1.cou_from;
	DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET done = 1;

	#/* On parcourt le curseur */
	OPEN cur_2way;

	REPEAT
		#/* Pour chaque enregistrement */
		FETCH cur_2way INTO land1, land2, troops1, troops2;
		IF NOT done THEN
			REPEAT
				#/* On lance un de pour chaque combattant */
				SET dice1=FUNC_ROLL_DICE(troops1);
				SET dice2=FUNC_ROLL_DICE(troops2);
				
				#/* On enleve un soldat a celui qui a le plus petit score. En cas d'egalite, chacun perd 1 */
				IF dice1>dice2 THEN
					SET dice2=dice2-1;
				ELSEIF dice2>dice1 THEN
					SET dice1=dice1-1;
				ELSE
					SET dice1=dice1-1;
					SET dice2=dice2-1;
				END IF;
			#/* jusqu'a epuisement d'un des deux */
			UNTIL troops1=0 OR troops2=0 END REPEAT;
			
			#/* si les troupes du premier sens arrivent a zero, on supprime la ligne, sinon, mise a jour de la table */
			IF troops1=0 THEN
				DELETE FROM mvc_actions_stack
				WHERE g_id=par_game_id
				AND cou_from=land1
				AND cou_to=land2;
			ELSE
				UPDATE mvc_actions_stack
				SET a_troops=troops1
				WHERE g_id=par_game_id
				AND cou_from=land1
				AND cou_to=land2;
			END IF;
			
			#/* idem pour les troupes dans l'autre sens de l'attaque */		
			IF troops2=0 THEN
				DELETE FROM mvc_actions_stack
				WHERE g_id=par_game_id
				AND cou_from=land2
				AND cou_to=land1;
			ELSE
				UPDATE mvc_actions_stack
				SET a_troops=troops2
				WHERE g_id=par_game_id
				AND cou_from=land2
				AND cou_to=land1;
			END IF;
			
		END IF;
	UNTIL done END REPEAT;

	CLOSE cur_2way;
	
	
	
END//

#/* Procedure qui verifie que tous les attaquants ne sont pas en mode "assist". S'ils le sont tous, ils passent tous en mode "conquerir ou mourir" */
DROP PROCEDURE IF EXISTS PROC_NOT_ALL_ASSISTERS//

CREATE PROCEDURE PROC_NOT_ALL_ASSISTERS(IN par_game_id INT(8))
BEGIN
	#/* On applique l'update pour l'ensemble des territoires attaques */
	UPDATE mvc_actions_stack
	SET a_priority=1
	WHERE g_id=par_game_id
	AND NOT EXISTS(	SELECT 'e'
			FROM mvc_actions A
			WHERE A.cou_to=cou_to
			AND A.g_id=par_game_id
			AND A.a_priority=1
		      );
END//

#/* Cree, pour chaque territoire, une attaque sans provenance(c'est comme cela qu'on exprime les defenseurs). Le territoire est donc vide */
DROP PROCEDURE IF EXISTS PROC_POSITION_DEFENDERS//

CREATE PROCEDURE PROC_POSITION_DEFENDERS(IN par_game_id INT(8))
BEGIN
	DECLARE land INT;
	DECLARE player INT;
	DECLARE nbTroops INT;
	DECLARE done INT DEFAULT 0;
	
	#/* curseur qui contient l'ensemble des territoires attaques, et le nombre de troupes qu'il y reste */
	DECLARE cur_attacked CURSOR FOR SELECT A.cou_to, L.l_strength, L.m_id
					FROM mvc_actions A, mvc_lands L
					WHERE A.g_id=par_game_id
					AND L.g_id=par_game_id
					AND A.cou_to=L.cou_id
					GROUP BY A.cou_to, L.l_strength, L.m_id;
	DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET done = 1;

	OPEN cur_attacked;
	
	#/* On le parcourt comme d'habitude */
	REPEAT
		FETCH cur_attacked INTO land,nbTroops, player;
		IF NOT done THEN
			#/* Pour chaque ligne, on rajoute l'attaque */
			INSERT INTO mvc_actions_stack(g_id, cou_to, a_strength, m_id, a_priority)
			VALUES (par_game_id, land, nbTroops, player, 1);
			
			#/* Et on met a zero les troupes restantes */
			UPDATE mvc_lands
			SET l_strength=0
			WHERE g_id=par_game_id
			AND cou_id=land;
		END IF;
	UNTIL done END REPEAT;

	CLOSE cur_attacked;

END//

#/* Procedure qui deroule un combat jusqu'a mort de tous les attaquants de priorite 1, ou bien jusqu'a mort du defenseur */
DROP PROCEDURE IF EXISTS PROC_CRUSH_DEFENDERS//

CREATE PROCEDURE PROC_CRUSH_DEFENDERS(IN par_game_id INT(8))
BEGIN
	DECLARE land INT;
	DECLARE defender INT;
	DECLARE defendingTroops INT;
	#/* Pour les attaquants, on aura en fait un de unique, qui contiendra le meilleur coup */
	DECLARE attackersMaxDice INT;
	DECLARE attackDiceResult INT;
	DECLARE defenderDice INT;
	DECLARE remainingAttackers INT;
	DECLARE attackerTroops INT;
	DECLARE done INT DEFAULT 0;
	#/* Curseur qui contient l'ensemble des territoires attaques */
	DECLARE cur_attacks CURSOR FOR SELECT DISTINCT A.cou_to, A.m_id, A.a_strength
					FROM mvc_actions_stack A
					WHERE A.g_id=par_game_id
					AND A.cou_from IS NULL;
	DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET done = 1;

	OPEN cur_attacks;
	
	REPEAT
		FETCH cur_attacks INTO land, defender, defendingTroops;
		IF NOT done THEN
			REPEAT
				#/* Pour chaque enregistrement */
				SET attackersMaxDice=0;
				
				#/* On met le de des attaquants a zero, et on compte le nombre 'attaquants priorite 1 restants */
				
				SELECT COUNT(*) INTO remainingAttackers
				FROM mvc_actions_stack A
				WHERE A.g_id=par_game_id
				AND A.cou_from IS NOT NULL
				AND A.a_priority=1;
				
				BEGIN
					
					DECLARE attackersDone INT DEFAULT 0;
					DECLARE cur_attackers CURSOR FOR SELECT A.a_strength
									FROM mvc_actions_stack A
									WHERE A.g_id=par_game_id
									AND A.cou_from IS NOT NULL
									AND A.cou_to=land;
					DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET attackersDone = 1;
					#/* Curseur pour l'ensemble des attaquants restants */
									
					SET defenderDice=FUNC_ROLL_DICE_FOR_TROOPS(defendingTroops);
					#/* On lance le de pour les defenseurs */
					OPEN cur_attackers;
					REPEAT
						FETCH cur_attackers INTO attackerTroops;
						IF NOT attackersDone THEN
							#/* pour chaque attaquant, on lance le de et on garde le score si c'est le meilleur qu'un attaquant ait obtenu */
							SET attackDiceResult=FUNC_ROLL_DICE_FOR_TROOPS(attackerTroops);
							IF attackDiceResult>attackersMaxDice THEN
								SET attackersMaxDice=attackDiceResult;
							END IF;		
						END IF;
					UNTIL attackersDone END REPEAT;
					CLOSE cur_attackers;
					
					#/* Si les attaquants ont un meilleur score que le defenseur */
					IF attackersMaxDice>defenderDice THEN
						#/* On reduit les troupes du defenseur (en variable) */
						SET defendingTroops = defendingTroops-1;
					ELSE
						#/* Sinon, le defenseur a un meilleur score (ou egalite, avantage au defenseur), et on reduit les troupes des attaquants */
						UPDATE mvc_actions_stack
						SET a_strength=a_strength-1
						WHERE A.g_id=par_game_id
						AND A.cou_to=land
						AND A.cou_from IS NOT NULL;
						
						#/* On supprime les attaquants qui n'ont plus de troupes */
						DELETE FROM mvc_actions_stack
						WHERE g_id=par_game_id
						AND a_strength=0;
						
						#/* On recompte les attaquants priorite 1 restants */
						SELECT COUNT(*) INTO remainingAttackers
						FROM mvc_actions_stack A
						WHERE A.g_id=par_game_id
						AND A.cou_from IS NOT NULL
						AND A.cou_to=land
						AND A.a_priority=1;
					END IF;
					
				END;
			
			#/* On arrete de boucler a l'elimination des attaquants priorite 1 ou du defenseur */		
			UNTIL remainingAttackers=0 OR defendingTroops=0 END REPEAT;
			
			#/* Si le defenseur n'a plus de troupes */
			IF defendingTroops=0 THEN
				#/* On supprime la defense de ce territoire de la table */
				DELETE FROM mvc_actions_stack
				WHERE g_id=par_game_id
				AND cou_to=land
				AND cou_from IS NULL;
			END IF;
			
		END IF;
	UNTIL done END REPEAT;

	CLOSE cur_attacks;

END//

#/* Fonction qui execute un match a mort entre tous les attaquants de priorite 1 sur un territoire, jusqu'a ce qu'il n'en reste plus qu'un, dont l'identifiant est retourne */
DROP FUNCTION IF EXISTS FUNC_ATTACKERS_DEATHMATCH//

CREATE FUNCTION FUNC_ATTACKERS_DEATHMATCH(par_game_id INT(8),par_land INT(8)) RETURNS INT
BEGIN
	DECLARE remainingAttackers INT DEFAULT 2;
	DECLARE bestDice INT;
	DECLARE bestAttacker INT;
	DECLARE currentAttacker INT;
	DECLARE hisTroops INT;
	DECLARE hisDice INT;
	DECLARE done INT DEFAULT 0;
	
	#/* On boucle jusqu'a elimination des concurrents */
	REPEAT
		BEGIN	
			#/* On prend l'ensemble des attaquants sur ce territoire, et leurs troupes */
			DECLARE cur_attackers CURSOR FOR SELECT A.m_id, A.a_strength
							FROM mvc_actions_stack A
							WHERE A.g_id=par_game_id
							AND A.cou_to=par_land
							AND A.a_priority=1;
			DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET done = 1;
			
			#/* On commence par definir le meilleur lancer de de a zero */
			SET bestDice=0;
			
			OPEN cur_attackers;
			REPEAT
				FETCH cur_attackers INTO currentAttacker, hisTroops;
				IF NOT done THEN
					#/* Pour chaque attaquant, on la nce son de */
					SET hisDice=FUNC_ROLL_DICE_FOR_TROOPS(hisTroops);
					#/* S'il depasse le meilleur score */
					IF hisDice>bestDice THEN
						#/* On efinit le meilleur score comme le sien, et lui-meme comme meilleur attaquant */
						SET bestDice=hisDice;
						SET bestAttacker=currentAttacker;
					END IF;
				END IF;
			#/* Jusqu'a fin du parcours des attaquants */
			UNTIL done END REPEAT;
			CLOSE cur_attackers;
			
			#/* On reduit les troupes de tous sauf le meilleur attaquant */
			UPDATE mvc_actions_stack
			SET a_strength=a_strength-1
			WHERE g_id=par_game_id
			AND cou_to=par_land
			AND a_priority=1
			AND m_id<>bestAttacker;
			
			#/* On supprime ceux qui n'ont plus de troupes */
			DELETE FROM mvc_actions_stack
			WHERE g_id=par_game_id
			AND cou_to=par_land
			AND a_strength=0;
			
			#/* On compte les attaquants restants */
			SELECT COUNT(*) INTO remainingAttackers
			FROM mvc_actions_stack A
			WHERE A.g_id=par_game_id
			AND A.cou_to=par_land
			AND a_priority=1;
		END;
	#/* On boucle jusqu'a ce que le nombre d'attaquants priorite 1 restants soit egal a 1 */
	UNTIL remainingAttackers=1 END REPEAT;
	#/* A la sortie, mecaniquement, le seul survivant sera dans la variable "meilleur attaquant" (les autres viennent de perdre une troupe, et le dernier d'entre eux vient de disparaitre, d'ou la sortiede boucle). On retourne donc cet identifiant */
	RETURN bestAttacker;
END//

#/* Procedure qui dit que pour chaque territoire (dont le defenseur a deja ete elimine), on effectue un combat a mort jusqu'a ce qu'il ne reste qu'un attaquant de priorite 1 */
DROP PROCEDURE IF EXISTS PROC_LMS_TAKES//

CREATE PROCEDURE PROC_LMS_TAKES(IN par_game_id INT(8))
BEGIN
	DECLARE land INT;
	DECLARE lastAttacker INT;
	DECLARE survivingTroops INT;
	DECLARE done INT DEFAULT 0;
	#/* On prend l'ensemble des territoires attaques */
	DECLARE cur_conflicts CURSOR FOR SELECT DISTINCT A.cou_to
					FROM mvc_actions_stack A
					WHERE A.g_id=par_game_id
					AND A.a_priority=1;
	DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET done = 1;

	OPEN cur_conflicts;
	
	REPEAT
		FETCH cur_conflicts INTO land;
		IF NOT done THEN
			#/* On joue le match a mort, ill ne nous reste qu'un attaquant */
			SET lastAttacker=FUNC_ATTACKERS_DEATHMATCH(par_game_id, land);
			
			#/* On regarde les troupes qu'il lui reste */
			SELECT A.a_strength INTO survivingTroops
			FROM mvc_actions_stack A
			WHERE A.g_id=par_game_id
			AND A.m_id=lastAttacker;
				
			#/* On lui fait prendre le territoire avec ces troupes */
			CALL PROC_DECLARE_CONQUEST(par_game_id, lastAttacker, land, survivingTroops);
			
		END IF;
	UNTIL done END REPEAT;

	CLOSE cur_conflicts;

END//

#/* Procedure qui fait rentrer les defenseurs dans leur territoire (tous les defenseurs qui ont survecu a la phase PROC_CRUSH_DEFENDERS, qui porte bien son nom, sont consideres comme victorieux) */
DROP PROCEDURE IF EXISTS PROC_DEFENDERS_BACK_HOME//

CREATE PROCEDURE PROC_DEFENDERS_BACK_HOME(IN par_game_id INT(8))
BEGIN
	#/* Il ne s'agit en fait que d'une requete UPDATE */
	UPDATE mvc_lands L
	SET L.l_strength=(SELECT a_strength
			FROM mvc_actions_stack A
			WHERE g_id=par_game_id
			AND A.cou_to=L.cou_id
			AND A.cou_to IS NULL
			)
	WHERE g_id=par_game_id
	AND EXISTS	(SELECT 'd'
			FROM mvc_actions_stack A
			WHERE g_id=par_game_id
			AND A.cou_to=L.cou_id
			AND A.cou_to IS NULL
			);
			
	#/* Et on supprime la ligne de defense */
	DELETE FROM mvc_actions_stack
	WHERE g_id=par_game_id
	AND cou_from IS NULL;
END//

#/* On renvoie chez eux ceux qui etaient en "assist" */
DROP PROCEDURE IF EXISTS PROC_ASSISTERS_BACK_HOME//

CREATE PROCEDURE PROC_ASSISTERS_BACK_HOME(IN par_game_id INT(8))
BEGIN
	#/* Ceux qui on */
	UPDATE mvc_lands L
	SET L.l_strength=L.l_strength+(SELECT A1.a_strength
					FROM mvc_actions_stack A1
					WHERE A1.g_id=par_game_id
					AND A1.cou_from=L.cou_id
					AND A1.m_id=L.m_id
					)
	WHERE EXISTS (SELECT 'a'
			FROM mvc_actions_stack A2
			WHERE A2.g_id=par_game_id
			AND A2.cou_from=L.cou_id
			AND A2.m_id=L.m_id
			);
	
	#/* On supprime les lignes de ceux qui sont rentres chez eux */
	DELETE FROM mvc_actions_stack
	WHERE g_id=par_game_id
	AND EXISTS	(SELECT 'a'
			FROM mvc_lands L
			WHERE L.g_id=par_game_id
			AND cou_from=L.cou_id
			AND m_id=L.m_id
			);
			
	#/* Pour les autres, on redirige leur attaque en priorite 1 vers leur territoire d'origine ("la reconquete ou la mort") */
	UPDATE mvc_actions_stack
	SET cou_to=cou_from
	WHERE g_id=par_game_id
	AND a_priority=0;
END//

#/* Procedure qui resout tous les combats d'une partie */
DROP PROCEDURE IF EXISTS PROC_SOLVE_ATTACKS//

CREATE PROCEDURE PROC_SOLVE_ATTACKS(IN par_game_id INT(8))
BEGIN
	
	#/* On extrait les troupes attaquantes de leur pays, on cree la liste des attaques */
	CALL PROC_CREATE_STACK(par_game_id);
	#/* On resout les attaques croisees */
	CALL PROC_SOLVE_2WAY_ATTACKS(par_game_id);
	#/* On regle les cas des pays qui ne sont attaques que par des "assisteurs" en les passant en "conquerant" */
	CALL PROC_NOT_ALL_ASSISTERS(par_game_id);
	#/* On place les defenseurs */
	CALL PROC_POSITION_DEFENDERS(par_game_id);
	#/* On effectue les combats "defenseurs contre attaquants" */
	CALL PROC_CRUSH_DEFENDERS(par_game_id);
	#/* On renvoie les defenseurs survivants chez eux */
	CALL PROC_DEFENDERS_BACK_HOME(par_game_id);
	#/* Dans les pays ou le defenseur a ete aneanti, on effectue les combats a mort et les livre aux vainqueurs */
	CALL PROC_LMS_TAKES(par_game_id);
	#/* On renvoie les "assisteurs" survivants chez eux, ce qui creera des attaques de type "conquerir ou mourir". Par contre on n'a plus un seul "assisteur" a present */
	CALL PROC_ASSISTERS_BACK_HOME(par_game_id);
	#/* Ceux qui viennent de prendre un territoire et ont une armee d'assist qui essaye de rentrer chez elle sont places en defenseurs */
	CALL PROC_POSITION_DEFENDERS(par_game_id);
	#/* On reeffectue une phase de combat, qui sera allegee du fait de la population de la table a cette etape */
	CALL PROC_CRUSH_DEFENDERS(par_game_id);
	#/* On renvoie les defenseurs survivants chez eux */
	CALL PROC_DEFENDERS_BACK_HOME(par_game_id);
	#/* Appel a la procedure LMS_TAKES, qui sera simpliste cette fois : pour simplifier, elle fera "les attaquants survivants a ce stade prennent le territoire" */
	CALL PROC_LMS_TAKES(par_game_id);
	
END//

#/* Procedure qui cree la pile d'attaques a resoudre */
DROP PROCEDURE IF EXISTS PROC_CREATE_STACK//

CREATE PROCEDURE PROC_CREATE_STACK(IN par_game_id INT(8))
BEGIN
	DECLARE var_deduct INT DEFAULT 0;
	DECLARE var_coufrom INT;
	DECLARE done INT DEFAULT 0;
	DECLARE cur_out CURSOR FOR SELECT DISTINCT cou_from FROM mvc_actions;
	#/* Curseur : l'ensemble des pays qui sont le point de depart d'au moins une attaque */
	DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET done = 1;

	OPEN cur_out;

	#/* Pour chacun */
	REPEAT
		FETCH cur_out INTO var_coufrom;
		IF NOT done THEN
			#/* On prend la somme des armees qui le quittent pour attaquer */
			SELECT SUM(A.a_strength) INTO var_deduct
			FROM mvc_actions A 
			WHERE A.cou_from = var_coufrom
			AND A.g_id=par_game_id;
			
			#/* On les deduit de ses troupes stationnees */			
			UPDATE mvc_lands
			SET l_strength=l_strength-var_deduct
			WHERE g_id=par_game_id;
		END IF;
	UNTIL done END REPEAT;

	CLOSE cur_out;
	
	#/* On copie les lignes de la table mvc_actions associees a cette partie dans la table mvc_actions_stack */
	INSERT INTO mvc_actions_stack(g_id, cou_from, cou_to, a_strength, a_priority)
		(SELECT A.g_id, A.cou_from, A.cou_to, A.a_strength, A.a_priority
		 FROM mvc_actions A
		 WHERE A.g_id=par_game_id
		);
	
	DELETE FROM mvc_actions WHERE g_id=par_game_id;
END//


delimiter ;
