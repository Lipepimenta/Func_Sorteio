<?php
class Sorteioc extends Controller {

	function sorteioc (){
        parent::Controller();
		$this->load->model('share');
		$this->load->Model('sorteio');
	}

	function teste(){
		$this->share->setAliasAcao(array(
			"menu_marcado"	=> 2006,
			"caminho_pao"	=> "Pesquisar",
			"acao_marcada"	=> "Pesquisar",
			"acoes"			=> array(
				"Pesquisar" => "sorteioc/teste",
				"Cadastrar" => "sorteioc/cadastroSorteio"
			)
		));

		if ($_POST) {
			$lista_sorteios = $this->sorteio->validaSorteio($this->input->post("info-perfil-nome"));
		} else {
			$lista_sorteios = $this->sorteio->listarSorteios();
		}
		$this->smarty->assign('lista_sorteios', $lista_sorteios);
		$this->smarty->assign('HEAD',$this->share->getHead());
		$this->smarty->assign('FOOT',$this->share->getFoot());
		$this->smarty->display('sorteio/teste.tpl');
	}

	function cadastroSorteio($id_sorteio=null){
		$this->share->setAliasAcao(
			array(
				"menu_marcado" => 2243,
				"caminho_pao" => "Pesquisar",
				"acao_marcada" => "Pesquisar",
				"acoes" => array()
			)
		);
		if ($_POST) {
			$titulo = $_POST["titulo"];
			$descricao = $_POST["descricao"];
			$data_sorteio = $_POST["data_sorteio"];
			$status = "ativo";
			if (trim($data_sorteio) != '' && $data_sorteio != '__/__/____') {

				$data_sorteio = explode('/', $data_sorteio);
				$data_sorteio = $data_sorteio[2].'-'.$data_sorteio[1].'-'.$data_sorteio[0];
			}
			if(strtotime($data_sorteio) < time()){
				$this->load->model('mensagem');
				$this->smarty->assign('msg', $this->mensagem->getMensagem('msg2505'));
				$this->smarty->assign('classMsg', 'msgError');
			} else {
				if ($id_sorteio != null) {
					$data_sorteio = date('Y-m-d', strtotime($data_sorteio));
					$alterar = $this->sorteio->atualizarSorteio($id_sorteio, $titulo, $descricao, $data_sorteio);
					if ($alterar) {
						$this->load->model('mensagem');
						$this->smarty->assign('msg', $this->mensagem->getMensagem('msg2504'));
						$this->smarty->assign('classMsg', 'msgOk');

						header("Location: {$this->config->config['base_url']}sorteioc/adicionarParticipante/$id_sorteio");
						exit();
					} else {
						$this->load->model('mensagem');
						$this->smarty->assign('msg', $this->mensagem->getMensagem('msg2505'));
						$this->smarty->assign('classMsg', 'msgError');
					}
				}else{
					$consulta = $this->sorteio->validaExistenciaSorteio($titulo);
					if ($consulta) {
						$this->load->model('mensagem');
						$this->smarty->assign('msg', $this->mensagem->getMensagem('msg2502'));
						$this->smarty->assign('classMsg', 'msgError');
					} else {
						$data_sorteio_formatada = date('Y-m-d', strtotime($data_sorteio));
						// Se o id_sorteio for nulo, cadastrar um novo sorteio
						$id_sorteio = $this->sorteio->cadastrarSorteio($titulo, $descricao, $data_sorteio_formatada, $status);
						if ($id_sorteio) {
							header("Location: /sorteioc/adicionarParticipante/$id_sorteio");
							exit;
						} else {
							$this->load->model('mensagem');
							$this->smarty->assign('msg', $this->mensagem->getMensagem('msg2506'));
							$this->smarty->assign('classMsg', 'msgError');
						}
					}
				}
			}
		}
		if($id_sorteio != null){
			$resultado = $this->sorteio->buscarIdSorteio($id_sorteio);
			$this->smarty->assign('resultadoid',$resultado);
		}

		$sorteios = $this->sorteio->listarSorteios();
		$this->smarty->assign('lista_sorteios',$sorteios);
		$this->smarty->assign('id_sorteio',$id_sorteio);
        $this->smarty->assign('HEAD',$this->share->getHead());
        $this->smarty->assign('FOOT',$this->share->getFoot());
		$this->smarty->display('sorteio/cadastro.tpl');
	}

	function adicionarParticipante($id_sorteio) {
		if ($_POST) {
			$nome = $_POST['nome'];

			$existeParticipante = $this->sorteio->verificarExistenciaParticipanteSorteio($nome, $id_sorteio);

			if ($existeParticipante) {
				$this->load->model('mensagem');
				$this->smarty->assign('msg', $this->mensagem->getMensagem('msg2507'));
				$this->smarty->assign('classMsg', 'msgError');
			} else {
				$retorno_sql = $this->sorteio->inserirParticipanteSorteio($nome, $id_sorteio);
				if($retorno_sql){
					$this->load->model('mensagem');
					$this->smarty->assign('msg', $this->mensagem->getMensagem('msg2508'));
					$this->smarty->assign('classMsg', 'msgOk');
				} else {
					$this->load->model('mensagem');
					$this->smarty->assign('msg', $this->mensagem->getMensagem('msg2511'));
					$this->smarty->assign('classMsg', 'msgError');
				}
			}
		}

		$participantesSorteio = $this->sorteio->obterParticipantesSorteio($id_sorteio);
		$this->smarty->assign('obterParticipantesSorteio',$participantesSorteio);
		$this->smarty->assign('id_sorteio', $id_sorteio);

		$this->smarty->assign('HEAD',$this->share->getHead());
        $this->smarty->assign('FOOT',$this->share->getFoot());
		$this->smarty->display('sorteio/adiciona-participantes.tpl');
	}

	function removerParticipanteSorteio($id_sorteio, $id_usuario){
		$removido = $this->sorteio->removerParticipanteSorteio($id_sorteio, $id_usuario);
		if ($removido) {
			//Redirecione de volta para a página de sorteio após ter removido com sucesso
			header("Location: {$this->config->config['base_url']}sorteioc/adicionarParticipante/$id_sorteio");
			exit();

			$this->load->model('mensagem');
			$this->smarty->assign('msg', $this->mensagem->getMensagem('msg2509'));
			$this->smarty->assign('classMsg', 'msgOk');
		} else {
			header("Location: $this->config->config['base_url']}sorteioc/adicionarParticipante/$id_sorteio");
			exit();

			$this->load->model('mensagem');
			$this->smarty->assign('msg', $this->mensagem->getMensagem('msg2510'));
			$this->smarty->assign('classMsg', 'msgError');
		}
	}

	function realizaSorteio($id_sorteio){
		if ($_POST) {
            // Obtém a lista de participantes que ainda não foram sorteados
            $participantes = $this->sorteio->obterParticipantesNaoSorteados($id_sorteio);
			if (!empty($participantes)) {
				shuffle($participantes);
				$vencedor = $participantes[0]['no_uid'];

				$this->sorteio->marcarParticipanteComoSorteado($vencedor, $id_sorteio);

				$this->load->model('mensagem');
				$this->smarty->assign('msg', $this->mensagem->getMensagem('msg2409'));
				$this->smarty->assign('classMsg', 'msgOk');
			} else {
				$this->load->model('mensagem');
				$this->smarty->assign('msg', $this->mensagem->getMensagem('msg2408'));
				$this->smarty->assign('classMsg', 'msgError');
            }
		}
		$participantesSorteio = $this->sorteio->obterVencedoresSorteio($id_sorteio);
		$this->smarty->assign('obterParticipantesSorteio',$participantesSorteio);
		$this->smarty->assign('id_sorteio', $id_sorteio);

		$this->smarty->assign('HEAD',$this->share->getHead());
        $this->smarty->assign('FOOT',$this->share->getFoot());
		$this->smarty->display('sorteio/sortear.tpl');
	}
	function participantes($id_sorteio) {

		$participantesSorteio = $this->sorteio->obterParticipantesSorteio($id_sorteio);
		$this->smarty->assign('obterParticipantesSorteio',$participantesSorteio);

		$vencedoresSorteio = $this->sorteio->listaVencedoresSorteio($id_sorteio);
		$this->smarty->assign('listaVencedoresSorteio',$vencedoresSorteio);

		$this->smarty->assign('id_sorteio', $id_sorteio);

		$this->smarty->assign('HEAD',$this->share->getHead());
        $this->smarty->assign('FOOT',$this->share->getFoot());
		$this->smarty->display('sorteio/participantes.tpl');
	}
}
?>