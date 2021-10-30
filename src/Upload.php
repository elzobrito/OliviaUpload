<?php

namespace OliviaUpload;

use Ramsey\Uuid\Uuid;

class Upload
{
    private $pasta;
    private $tamanho;
    private $extensoes;
    // O nome original do arquivo no computador do usuário
    private $arqName;
    private $arqNameCripto;
    // O tipo mime do arquivo. Um exemplo pode ser "image/gif"
    private $arqType;
    // O tamanho, em bytes, do arquivo
    private $arqSize;
    // O nome temporário do arquivo, como foi guardado no servidor
    private $arqTemp;
    // O código de erro associado a este upload de arquivo
    private $arqError = 0;

    //Altera a permissão da pasta
    private $permissao;
    private $msgError = array();

    public function getError()
    {
        return $this->msgError;
    }

    function getErro()
    {
        return $this->arqError;
    }

    function getArqNameCripto()
    {
        return $this->arqNameCripto;
    }

    function getArqName()
    {
        return $this->arqName;
    }

    private function renomear($param)
    {
        if ($param) {
            $uuid = Uuid::uuid4();
            $nome = $this->arqName;
            $ext = explode('.', $nome);
            $ext = strtolower(end($ext));
            $this->arqNameCripto = $uuid->toString() . "." . $ext;
        }
    }

    private function valida_extensao()
    {
        if (!in_array($this->arqType, $this->extensoes)) {
            $this->msgError[] = 'O tipo de arquivo enviado é inválido!';
            $this->arqError = 1;
            return false;
        }
        return true;
    }

    private function valida_tamanho()
    {
        if ((1024 * 1024 * $this->tamanho) < $this->arqSize) {
            $this->msgError[] = 'O tamanho do arquivo enviado é maior que o limite!';
            $this->arqError = 1;
            return false;
        }
        return true;
    }

    private function mover()
    {
        if ($this->arqError == 0 && $this->valida_extensao() && $this->valida_tamanho()) {
            if ($this->arqNameCripto != "") {
                return move_uploaded_file($this->arqTemp, $this->pasta .  DIRECTORY_SEPARATOR . $this->arqNameCripto);
            } else {

                return move_uploaded_file($this->arqTemp, $this->pasta . $this->arqName);
            }
        }
    }

    public function delete_file()
    {
        $arquivo = $this->pasta . DIRECTORY_SEPARATOR . ($this->arqNameCripto != null ? $this->arqNameCripto : $this->arqName);
        if (is_file($arquivo))
            unlink($arquivo);
    }

    private function executa()
    {
        if ($this->arqError == 0) {
            if ($this->permissao != null)
                chmod($this->pasta, 0777);
            $this->mover();
            if ($this->permissao != null)
                chmod($this->pasta, 0775);
        }
    }

    function __construct($pasta, $tamanho, $extensoes, $arquivo, $renomear, $permissao = null)
    {
        $this->pasta = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI'] .  DIRECTORY_SEPARATOR . $pasta;

        $this->tamanho = $tamanho;
        $this->extensoes = $extensoes;
        $this->arquivo = $arquivo;
        $this->permissao = $permissao;
        // O nome original do arquivo no computador do usuário
        $this->arqName = $arquivo['name'];
        $arq = pathinfo($this->pasta . DIRECTORY_SEPARATOR . $this->arqName);
        // O tipo mime do arquivo. Um exemplo pode ser "image/gif"
        if ($this->arqName != '') {
            $tmpType = $arq['extension'];
        } else {
            $tmpType = array();
        }
        $this->arqType = $tmpType;
        // O tamanho, em bytes, do arquivo
        $this->arqSize = $arquivo['size'];
        // O nome temporário do arquivo, como foi guardado no servidor
        $this->arqTemp = $arquivo['tmp_name'];
        // O código de erro associado a este upload de arquivo
        $this->arqError = $arquivo['error'];
        $this->renomear($renomear);
        $this->executa();
    }
}
