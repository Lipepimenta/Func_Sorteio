<?php
	class Sorteio extends Model{
		public function Sorteio(){
			parent :: Model();
		}

		function cadastrarSorteio($titulo, $descricao, $data_sorteio, $status) {
			$status = "ativo";
			$inserir_sql = "INSERT INTO sorteios (titulo, descricao, data_sorteio, status)
							VALUES ('$titulo', '$descricao', '$data_sorteio', '$status')";
			$sql_prepare = $this->conexao->Prepare($inserir_sql);
			$resultado=$this->conexao->Execute($sql_prepare);

			$id_sorteio = $this->conexao->Insert_ID();
			return $id_sorteio;
		}

		function listarSorteios() {
			$consultaSorteios = "SELECT id, titulo FROM sorteios";
			$resultadoSorteios = $this->conexao->getAll($consultaSorteios);

			return $resultadoSorteios;
		}

		function buscarIdSorteio($id_sorteio){
			$consultaSorteios = "SELECT * FROM sorteios WHERE id = $id_sorteio";
			$resultado = $this->conexao->getrow($consultaSorteios);

			return $resultado;
		}
		function validaExistenciaSorteio($titulo){
			$consultaSorteios = "SELECT * FROM sorteios WHERE titulo = '$titulo'";
			$resultado = $this->conexao->getrow($consultaSorteios);

			return $resultado;
		}

		function validaSorteio($titulo){
			$consultaSorteios = "SELECT * FROM sorteios WHERE titulo like '%$titulo%'";
			$resultado = $this->conexao->getAll($consultaSorteios);

			return $resultado;
		}

		function obterParticipantesSorteio($id_sorteio) {
			// Verifica se o id_sorteio está definido e é um número válido
			if (isset($id_sorteio) && is_numeric($id_sorteio)) {
				$consulta_sql = "SELECT candidatos_sorteios.no_uid, candidatos_sorteios.id_sorteio
								 FROM candidatos_sorteios
								 WHERE candidatos_sorteios.id_sorteio = $id_sorteio";

				$resultado = $this->conexao->getAll($consulta_sql);
				return $resultado;
			}
		}

		function obterParticipantesSorteioPremiados($id_sorteio) {
			if (isset($id_sorteio) && is_numeric($id_sorteio)) {
				$consulta_sql = "SELECT
									    c.no_uid, vs.id_sorteio,
									CASE
									WHEN vs.id_sorteio is null THEN 'não'
									ELSE 'sim'
								END as Premiado
								FROM
									candidatos_sorteios c
								LEFT JOIN
									vencedores_sorteios vs on vs.id_sorteio = c.id_sorteio and vs.no_uid = c.no_uid
								WHERE
									c.id_sorteio = $id_sorteio;";
				$resultado = $this->conexao->getAll($consulta_sql);
				return $resultado;
			}
		}

		function obterParticipantesNaoSorteados($id_sorteio) {
			$consulta_sql = "SELECT tb_usuario.no_uid, tb_usuario.no_uid
					  FROM tb_usuario
					  INNER JOIN candidatos_sorteios ON candidatos_sorteios.no_uid = tb_usuario.no_uid COLLATE latin1_swedish_ci
					  WHERE candidatos_sorteios.id_sorteio = $id_sorteio
					  AND tb_usuario.no_uid COLLATE latin1_swedish_ci NOT IN (
						  SELECT no_uid FROM vencedores_sorteios WHERE id_sorteio = $id_sorteio
					  )";
			$resultado = $this->conexao->getAll($consulta_sql);
			return $resultado;
		}

		function verificarExistenciaParticipanteSorteio($nome, $id_sorteio) {
			$consulta_sql = "SELECT * FROM candidatos_sorteios
			INNER JOIN tb_usuario ON candidatos_sorteios.no_uid = tb_usuario.no_uid COLLATE latin1_swedish_ci
			WHERE tb_usuario.no_uid = '$nome' AND candidatos_sorteios.id_sorteio = $id_sorteio";

			$resultado = $this->conexao->getAll($consulta_sql);
			if (count($resultado)> 0) {
				return true; // O participante existe no sorteio
			}
			return false; // O participante não existe no sorteio ou valores nulos
		}

		function inserirParticipantePremiado($no_uid, $id_sorteio) {
			// Obtenha o ID do usuário com base no nome
			if ($no_uid !== null) {
				// Insira o participante premiado na tabela vencedores_sorteios
				$data_vitoria = date("Y-m-d H:i:s");
				$inserir_sql = "INSERT INTO vencedores_sorteios (id_sorteio, no_uid, data_vitoria)
								VALUES ($id_sorteio, $no_uid, '$data_vitoria')";
				$sql_prepare = $this->conexao->Prepare($inserir_sql);
				$resultado=$this->conexao->Execute($sql_prepare);

				return $resultado;
			}
		}

		function inserirParticipanteSorteio($id_pessoa, $id_sorteio) {
			$consulta_sql = "SELECT * FROM tb_usuario WHERE no_uid = '$id_pessoa'";
			$consulta_prepare = $this->conexao->Prepare($consulta_sql);
			$consulta_resultado = $this->conexao->Execute($consulta_prepare);

			if (!$consulta_resultado) {
				return; // Ocorreu um erro na consulta
			}

			if ($consulta_resultado->RecordCount() == 0) {
				return; // O nome do usuário não foi encontrado na tabela tb_usuario
			}

			$inserir_sql = "INSERT INTO candidatos_sorteios (no_uid, id_sorteio) VALUES ('$id_pessoa', '$id_sorteio')";
			$sql_prepare = $this->conexao->Prepare($inserir_sql);
			$resultado = $this->conexao->Execute($sql_prepare);
			return $resultado;
		}

		function marcarParticipanteComoSorteado($nome_participante, $id_sorteio) {
			$inserir_sql = "INSERT INTO vencedores_sorteios (no_uid, id_sorteio, data_vitoria)
					  VALUES ((SELECT no_uid  FROM tb_usuario WHERE no_uid = '$nome_participante'), $id_sorteio, NOW())";

			$sql_prepare = $this->conexao->Prepare($inserir_sql);
			$resultado=$this->conexao->Execute($sql_prepare);

			return $resultado;
		}

		function removerParticipanteSorteio($id_sorteio, $id_usuario) {
			$verificar_sql = "SELECT id_sorteio FROM candidatos_sorteios WHERE id_sorteio = $id_sorteio AND no_uid = '$id_usuario'";
			$resultado = $this->conexao->getAll($verificar_sql);
			if (count($resultado) === 1) {
				$remover_sql = "DELETE FROM candidatos_sorteios WHERE id_sorteio = $id_sorteio AND no_uid = '$id_usuario'";

				$sql_prepare = $this->conexao->Prepare($remover_sql);
				$resultado=$this->conexao->Execute($sql_prepare);

				return $resultado;
			} else {
				return false; // O participante não está na lista
			}
		}

		function obterVencedoresSorteio($id_sorteio) {
			// Verifica se o id_sorteio está definido e é um número válido
			if (isset($id_sorteio) && is_numeric($id_sorteio)) {
				$consulta_sql = "SELECT vencedores_sorteios.data_vitoria, tb_usuario.no_uid, sorteios.titulo AS nome_sorteio
								 FROM vencedores_sorteios
								 INNER JOIN tb_usuario ON vencedores_sorteios.no_uid = tb_usuario.no_uid  COLLATE latin1_swedish_ci
								 INNER JOIN sorteios ON vencedores_sorteios.id_sorteio = sorteios.id
								 WHERE vencedores_sorteios.id_sorteio = $id_sorteio";
				$resultado = $this->conexao->getAll($consulta_sql);
				return $resultado;
			} else {
				return array();
			}
		}

		function listaVencedoresSorteio($id_sorteio) {
			if (isset($id_sorteio) && is_numeric($id_sorteio)) {
				$consulta_sql = "SELECT vencedores_sorteios.no_uid
								 FROM vencedores_sorteios
								 WHERE vencedores_sorteios.id_sorteio = $id_sorteio";
				$resultado = $this->conexao->getAll($consulta_sql);
				$vencedores = array();

				foreach ($resultado as $row) {
					$vencedores[] = $row['no_uid'];
				}
				return $vencedores;
			} else {
				return array();
			}
		}

		function atualizarSorteio($id_sorteio, $novo_titulo, $nova_descricao, $nova_data_sorteio) {
			// Verifique se o sorteio existe antes de atualizar
			$verificar_sql = "SELECT id FROM sorteios WHERE id = $id_sorteio";
			$resultado = $this->conexao->getAll($verificar_sql);

			if (count($resultado) > 0) {
				$atualizar_sql = "UPDATE sorteios
								 SET titulo = '$novo_titulo',
									 descricao = '$nova_descricao',
									 data_sorteio = '$nova_data_sorteio'
								 WHERE id = $id_sorteio";
				$sql_prepare = $this->conexao->Prepare($atualizar_sql);
				$resultado = $this->conexao->Execute($sql_prepare);

				return $resultado;
			} else {
				return false; // O sorteio não existe
			}
		}

	}
?>