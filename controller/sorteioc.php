<?php
class Sorteioc extends Controller {

	function sorteioc() {
        parent::Controller();
		$this->load->model('share');
		$this->load->Model('sorteio');
		$this->load->model('mensagem');
	}

	function teste() {
		$this->share->setAliasAcao(array(
			"menu_marcado"	=> 2250,
			"caminho_pao"	=> "Pesquisar",
			"acao_marcada"	=> "Pesquisar",
			"acoes"			=> array(
				"Pesquisar" => "sorteioc/teste",
				"Cadastrar" => "sorteioc/cadastroSorteio"
			)
		));

		$info_perfil_nome = $this->input->post("info-perfil-nome");
    	$lista_sorteios = $this->sorteio->listarSorteios($info_perfil_nome);

		$this->smarty->assign('lista_sorteios', $lista_sorteios);
		$this->smarty->assign('HEAD',$this->share->getHead());
		$this->smarty->assign('FOOT',$this->share->getFoot());
		$this->smarty->display('sorteio/teste.tpl');
	}

	function cadastroSorteio($id_sorteio=null) {
		$this->share->setAliasAcao(
			array(
				"menu_marcado" => 2250,
				"caminho_pao" => "Pesquisar",
				"acao_marcada" => "Pesquisar",
				"acoes" => array()
			)
		);
		if($_POST) {
			$titulo = $_POST["titulo"];
			$descricao = $_POST["descricao"];
			$data_sorteio = $_POST["data_sorteio"];
			$status = "ativo";
			if(trim($data_sorteio) != '' && $data_sorteio != '__/__/____') {
				$data_sorteio = explode('/', $data_sorteio);
				$data_sorteio = $data_sorteio[2].'-'.$data_sorteio[1].'-'.$data_sorteio[0];
			}

			if($id_sorteio != null) {
				$data_sorteio = date('Y-m-d', strtotime($data_sorteio));

				$this->sorteio->id=$id_sorteio;
				$this->sorteio->load();
				$this->sorteio->titulo = $titulo;
				$this->sorteio->descricao = $descricao;
				$this->sorteio->data_sorteio = $data_sorteio;

				$alterar = $this->sorteio->alterar();
				if($alterar) {
					header("Location: {$this->config->config['base_url']}sorteioc/adicionarParticipante/$id_sorteio/msg2412/msgOk");
					exit();
				}else{
					$this->smarty->assign('msg', $this->mensagem->getMensagem('msg2413'));
					$this->smarty->assign('classMsg', 'msgError');
				}
			}else{
				$consulta = $this->sorteio->validaExistenciaSorteio($titulo);
				if($consulta) {
					$this->smarty->assign('msg', $this->mensagem->getMensagem('msg2411'));
					$this->smarty->assign('classMsg', 'msgError');
				}else{
					$data_sorteio_formatada = date('Y-m-d', strtotime($data_sorteio));
					$this->sorteio = new Sorteio();
					$this->sorteio->titulo = $titulo;
					$this->sorteio->descricao = $descricao;
					$this->sorteio->data_sorteio = $data_sorteio_formatada;
					$this->sorteio->status = $status;
					// Se o id_sorteio for nulo, cadastrar um novo sorteio
					$this->sorteio->inserir();
					$id_sorteio = $this->conexao->Insert_ID();
					if($id_sorteio) {
						header("Location: /sorteioc/adicionarParticipante/$id_sorteio");
						exit;
					}else{
						$this->smarty->assign('msg', $this->mensagem->getMensagem('msg2414'));
						$this->smarty->assign('classMsg', 'msgError');
					}
				}
			}
		}

		if($id_sorteio != null){
			$resultado = $this->sorteio->buscarIdSorteio($id_sorteio);
			$this->smarty->assign('resultadoid', $resultado);
		}

		$sorteios = $this->sorteio->listarSorteios();
		$this->smarty->assign('lista_sorteios',$sorteios);
		$this->smarty->assign('id_sorteio',$id_sorteio);
        $this->smarty->assign('HEAD',$this->share->getHead());
        $this->smarty->assign('FOOT',$this->share->getFoot());
		$this->smarty->display('sorteio/cadastro.tpl');
	}

	function adicionarParticipante($id_sorteio, $msg = null, $classMsg = null) {
		if ($msg != null) {
			$this->smarty->assign('msg', $this->mensagem->getMensagem($msg));
			$this->smarty->assign('classMsg', $classMsg);
		}
		if($_POST) {
			$nome = $_POST['nome'];
			$existe_participante = $this->sorteio->verificarExistenciaParticipanteSorteio($nome, $id_sorteio);
			if($existe_participante) {
				$this->smarty->assign('msg', $this->mensagem->getMensagem('msg2415'));
				$this->smarty->assign('classMsg', 'msgError');
			}else{
				$retorno_sql = $this->sorteio->inserirParticipanteSorteio($nome, $id_sorteio);
				if($retorno_sql) {
					$this->smarty->assign('msg', $this->mensagem->getMensagem('msg2410'));
					$this->smarty->assign('classMsg', 'msgOk');
				}else{
					$this->smarty->assign('msg', $this->mensagem->getMensagem('msg2418'));
					$this->smarty->assign('classMsg', 'msgError');
				}
			}
		}
		$participantes_sorteio = $this->sorteio->obterParticipantesSorteio($id_sorteio);
		$this->smarty->assign('obterParticipantesSorteio',$participantes_sorteio);
		$this->smarty->assign('id_sorteio', $id_sorteio);
		$this->smarty->assign('HEAD',$this->share->getHead());
        $this->smarty->assign('FOOT',$this->share->getFoot());
		$this->smarty->display('sorteio/adiciona-participantes.tpl');
	}

	function removerParticipanteSorteio($id_sorteio, $id_usuario) {
		$this->share->setAliasAcao(array(
			"menu_marcado"  => 2250,
			"caminho_pao"   => "Pesquisar",
			"acao_marcada"  => "Pesquisar",
			"acoes"         => array()
		));

		$retorna_sql = $this->sorteio->removerParticipanteSorteio($id_sorteio, $id_usuario);
		if ($retorna_sql) {
			header("Location: {$this->config->config['base_url']}sorteioc/adicionarParticipante/$id_sorteio/msg2416/msgOk");
			exit();
		}else {
			header("Location: {$this->config->config['base_url']}sorteioc/adicionarParticipante/$id_sorteio/msg2417/msgError");
			exit();
		}
	}

	function realizaSorteio($id_sorteio) {
		if($_POST) {
            // Obtém a lista de participantes que ainda não foram sorteados
            $participantes = $this->sorteio->obterParticipantesNaoSorteados($id_sorteio);
			if(!empty($participantes)) {
				shuffle($participantes);
				$vencedor = $participantes[0]['no_uid'];

				$this->sorteio->marcarParticipanteComoSorteado($vencedor, $id_sorteio);

				$this->smarty->assign('msg', $this->mensagem->getMensagem('msg2409'));
				$this->smarty->assign('classMsg', 'msgOk');
			}else{
				$this->smarty->assign('msg', $this->mensagem->getMensagem('msg2419'));
				$this->smarty->assign('classMsg', 'msgError');
            }
		}
		$participantes_sorteio = $this->sorteio->obterVencedoresSorteio($id_sorteio);
		$this->smarty->assign('obterParticipantesSorteio', $participantes_sorteio);
		$this->smarty->assign('id_sorteio', $id_sorteio);
		$this->smarty->assign('HEAD',$this->share->getHead());
        $this->smarty->assign('FOOT',$this->share->getFoot());
		$this->smarty->display('sorteio/sortear.tpl');
	}

	function participantes($id_sorteio) {
		$participantes_sorteio = $this->sorteio->obterParticipantesSorteio($id_sorteio);
		foreach($participantes_sorteio as $participante) {
			$participante['sequencia'] = null;
		}
		$vencedores_sorteio = $this->sorteio->listaVencedoresSorteio($id_sorteio);
		$sequenciaAtual = 1;
		foreach($participantes_sorteio as $chave => &$participante) {
			if(in_array($participante['no_uid'], $vencedores_sorteio)) {
				$participante['sequencia'] = $sequenciaAtual;
				$sequenciaAtual++;
			}
		}
		$this->smarty->assign('listaVencedoresSorteio', $vencedores_sorteio);
		$this->smarty->assign('obterParticipantesSorteio', $participantes_sorteio);
		$this->smarty->assign('id_sorteio', $id_sorteio);
		$this->smarty->assign('HEAD',$this->share->getHead());
        $this->smarty->assign('FOOT',$this->share->getFoot());
		$this->smarty->display('sorteio/participantes.tpl');
	}
}
?>
