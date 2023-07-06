<?php

use Dompdf\Dompdf;

class Algoritma extends CI_Controller{
	public function __construct()
	{
		parent::__construct();
		if ($this->session->login['role'] != 'petugas' && $this->session->login['role'] != 'admin') redirect();
		$this->data['aktif'] = 'algoritma';
		$this->load->model('M_barang', 'm_barang');
		$this->load->model('M_barang_terjual', 'm_barang_terjual');
	}

	public function index()
	{
		$this->data['title'] = 'Clustering';
		$this->load->view('algoritma/input', $this->data);
	}

	//Proses clustering k-means
	public function proses_cluster()
	{
		$this->data['title'] = 'Hasil Clustering';

		// Pengambilan Parameter Cluster Input
		$cluster1 = $this->input->post('cluster1');
		$cluster2 = $this->input->post('cluster2');
		$cluster3 = $this->input->post('cluster3');

		$error = false;

		//Membagi CLuster misal dari 100;50 menjadi dipisah menjadi [100, 50]
		$arrcluster1 = explode(';', $cluster1);
		if(count($arrcluster1) != 3){
			$error = true;
		}
		$arrcluster2 = explode(';', $cluster2);
		if(count($arrcluster2) != 3){
			$error = true;
		} 
		$arrcluster3 = explode(';', $cluster3);
		if(count($arrcluster3) != 3){
			$error = true;
		}

		//Notif Error jika input tidak dimasukkan dengan benar
		if ($error) {
			$this->session->set_flashdata('error', 'Masukkan Nilai Custer Dengan Benar!');
			redirect('algoritma');
		}

		$this->data['cluster1'] = $cluster1;
		$this->data['cluster2'] = $cluster2;
		$this->data['cluster2'] = $cluster2;

		//Mengambil data-data barang dan jumlah penjualan untuk diolah
		$originalData1 = $this->m_barang->barangAlgo();
		$originalData2 = $this->m_barang_terjual->terjualAlgo();
		$originalData = $originalData1 . $originalData2;

		$index= 0;

		$hasilIterasi = "";

		//Melakukan iterasi terdapat data
		while(true){
			$this->data['iterasi'.$index] = [];
			// Inisialisasi Variabel
			$countKenaC1 = 0;
			$totalOriLabaC1 = 0;
			$totalOriTransC1 = 0;
			$totalOriJumlahC1 = 0;
			$countKenaC2 = 0;
			$totalOriLabaC2 = 0;
			$totalOriTransC2 = 0;
			$totalOriJumlahC2 = 0;
			$countKenaC3 = 0;
			$totalOriLabaC3 = 0;
			$totalOriTransC3 = 0;
			$totalOriJumlahC3 = 0;

			$tempResult = "";
		
			//Proses Perhitungan Euclidian Distance dari semua data
			foreach($originalData as $data){
				//Menyimpan cluster setiap iterasi
				$this->data["algo"]['iterasi'.$index]["cluster1"] = $cluster1;
				$this->data["algo"]['iterasi'.$index]["cluster2"] = $cluster2;
				$this->data["algo"]['iterasi'.$index]["cluster3"] = $cluster3;

				//Penghitungan Euclidian
				$c1 = sqrt(pow($data->laba - $arrcluster1[0], 2) + pow($data->transaksi - $arrcluster1[1], 2) + pow($data->jumlah - $arrcluster1[1], 2));
				$c2 = sqrt(pow($data->laba - $arrcluster2[0], 2) + pow($data->transaksi - $arrcluster2[1], 2) + pow($data->jumlah - $arrcluster2[1], 2));
				$c3 = sqrt(pow($data->laba - $arrcluster3[0], 2) + pow($data->transaksi - $arrcluster3[1], 2) + pow($data->jumlah - $arrcluster3[1], 2));

				//Menyimpan hasil perhitungan iterasi
				$this->data["algo"]['iterasi'.$index]["c1"][] = $c1;
				$this->data["algo"]['iterasi'.$index]["c2"][] = $c2;
				$this->data["algo"]['iterasi'.$index]["c3"][] = $c3;

				// Cluster classification
				$this->data['algo']['iterasi'.$index]['group'][] = $this->classifyingCluster($c1, $c2, $c3);

				//Perbandingan untuk menentukan hasilnya C1, C2 atau C3
				if($c1 < $c2 && $c1 < $c3){
					$tempResult .= "C1";
					$totalOriLabaC1 += $data->laba;
					$totalOriTransC1 += $data->transaksi;
					$totalOriJumlahC1 += $data->jumlah;
					$countKenaC1 += 1;
				}else if($c2 < $c3){
					$tempResult .= "C2";
					$totalOriLabaC2 += $data->laba;
					$totalOriTransC2 += $data->transaksi;
					$totalOriJumlahC2 += $data->jumlah;
					$countKenaC2 += 1;
				}else {
					$tempResult .= "C3";
					$totalOriLabaC3 += $data->laba;
					$totalOriTransC3 += $data->transaksi;
					$totalOriJumlahC3 += $data->jumlah;
					$countKenaC3 += 1;
				}
			}

			//Pengecekan Apakah hasil iterasi saat ini sama dengan hasil iterasi sebelumnya? jika sama maka iterasi berhenti
			if($hasilIterasi == $tempResult) {
				break;
			}

			//Perhitungan untuk mendapatkan cluster yang baru
			$cluster1 = ($countKenaC1==0) ? "0;0":$totalOriLabaC1/($countKenaC1) . ";" . $totalOriTransC1/($countKenaC1) . ";" . $totalOriJumlahC1/($countKenaC1);
			$cluster2 = ($countKenaC2==0) ? "0;0":$totalOriLabaC2/($countKenaC2) . ";" . $totalOriTransC2/($countKenaC2) . ";" . $totalOriJumlahC2/($countKenaC1);
			$cluster3 = ($countKenaC3==0) ? "0;0":$totalOriLabaC3/($countKenaC3) . ";" . $totalOriTransC3/($countKenaC3) . ";" . $totalOriJumlahC3/($countKenaC1);

			$arrcluster1 = explode(';', $cluster1);
			$arrcluster2 = explode(';', $cluster2);
			$arrcluster3 = explode(';', $cluster3);

			$index++;

			$hasilIterasi = $tempResult;
			
		}

		$iterasi = [];

		$this->data['ori'] = $originalData;

		$this->data['laba_transaksi_jumlah'] = $this->calculateTotalTransactionAndProfit(
			$originalData, $this->data['algo']
		);

		$this->load->view('algoritma/result', $this->data);
	}

	private function calculateTotalTransactionAndProfit(
		array $dataOrigin,
		array $dataAlgo
	): array {
		$result = [
			'laba' => [
				'c1' 	  => ['min' => 0, 'max' => 0, 'mean' => 0, 'std' => 0, 'items' => []],
				'c2' 	  => ['min' => 0, 'max' => 0, 'mean' => 0, 'std' => 0, 'items' => []],
				'c3' 	  => ['min' => 0, 'max' => 0, 'mean' => 0, 'std' => 0, 'items' => []],
				'items' => []
			],
			'transaksi' => [
				'c1'    => ['min' => 0, 'max' => 0, 'mean' => 0, 'std' => 0, 'items' => []],
				'c2'    => ['min' => 0, 'max' => 0, 'mean' => 0, 'std' => 0, 'items' => []],
				'c3'    => ['min' => 0, 'max' => 0, 'mean' => 0, 'std' => 0, 'items' => []],
				'items' => []
			],
			'jumlah' => [
				'c1'    => ['min' => 0, 'max' => 0, 'mean' => 0, 'std' => 0, 'items' => []],
				'c2'    => ['min' => 0, 'max' => 0, 'mean' => 0, 'std' => 0, 'items' => []],
				'c3'    => ['min' => 0, 'max' => 0, 'mean' => 0, 'std' => 0, 'items' => []],
				'items' => []
			],
		];

		$lastIteration 		 = array_key_last($dataAlgo);
		$lastIterationData = $dataAlgo[$lastIteration];

		// Find min and max value
		$tempAlgoCalculation['min']['c1'] = min($lastIterationData['c1']);
		$tempAlgoCalculation['min']['c2'] = min($lastIterationData['c2']);
		$tempAlgoCalculation['min']['c3'] = min($lastIterationData['c3']);
		$tempAlgoCalculation['max']['c1'] = max($lastIterationData['c1']);
		$tempAlgoCalculation['max']['c2'] = max($lastIterationData['c2']);
		$tempAlgoCalculation['max']['c3'] = max($lastIterationData['c3']);

		// Find index of every min and max value
		$maxIndexC1 = array_search($tempAlgoCalculation['max']['c1'], $lastIterationData['c1']);
		$maxIndexC2 = array_search($tempAlgoCalculation['max']['c2'], $lastIterationData['c2']);
		$maxIndexC3 = array_search($tempAlgoCalculation['max']['c3'], $lastIterationData['c3']);
		$minIndexC1 = array_search($tempAlgoCalculation['min']['c1'], $lastIterationData['c1']);
		$minIndexC2 = array_search($tempAlgoCalculation['min']['c2'], $lastIterationData['c2']);
		$minIndexC3 = array_search($tempAlgoCalculation['min']['c3'], $lastIterationData['c3']);

		// Flatten laba & transaksi into 1D array
		foreach ($dataOrigin as $item) {
			$result['laba']['items'][] 	= (float)$item->laba;
			$result['transaksi']['items'][] = (float)$item->transaksi;
			$result['jumlah']['items'][] = (float)$item->jumlah;
		}

		// Assign the value to result
		$result['laba']['c1']['min'] = $result['laba']['items'][$minIndexC1];
		$result['laba']['c2']['min'] = $result['laba']['items'][$minIndexC2];
		$result['laba']['c3']['min'] = $result['laba']['items'][$minIndexC3];
		$result['transaksi']['c1']['min'] = $result['transaksi']['items'][$minIndexC1];
		$result['transaksi']['c2']['min'] = $result['transaksi']['items'][$minIndexC2];
		$result['transaksi']['c3']['min'] = $result['transaksi']['items'][$minIndexC3];
		$result['jumlah']['c1']['min'] = $result['laba']['items'][$minIndexC1];
		$result['jumlah']['c2']['min'] = $result['laba']['items'][$minIndexC2];
		$result['jumlah']['c3']['min'] = $result['laba']['items'][$minIndexC3];

		$result['laba']['c1']['max'] = $result['laba']['items'][$maxIndexC1];
		$result['laba']['c2']['max'] = $result['laba']['items'][$maxIndexC2];
		$result['laba']['c3']['max'] = $result['laba']['items'][$maxIndexC3];
		$result['transaksi']['c1']['max'] = $result['transaksi']['items'][$maxIndexC1];
		$result['transaksi']['c2']['max'] = $result['transaksi']['items'][$maxIndexC2];
		$result['transaksi']['c3']['max'] = $result['transaksi']['items'][$maxIndexC3];
		$result['jumlah']['c1']['max'] = $result['laba']['items'][$maxIndexC1];
		$result['jumlah']['c2']['max'] = $result['laba']['items'][$maxIndexC2];
		$result['jumlah']['c3']['max'] = $result['laba']['items'][$maxIndexC3];

		$validCluster = [];
		for ($clusterIndex=0; $clusterIndex < count($lastIterationData['group']); $clusterIndex++) {
			$clusterClassification = $lastIterationData['group'][$clusterIndex];

			// Fetch valid items based on cluster classification
			$result['laba'][$clusterClassification]['items'][] = $result['laba']['items'][$clusterIndex];
			$result['transaksi'][$clusterClassification]['items'][] += $result['transaksi']['items'][$clusterIndex];
			$result['jumlah'][$clusterClassification]['items'][] += $result['jumlah']['items'][$clusterIndex];

			if (!in_array($clusterClassification, $validCluster))
				$validCluster[] = $clusterClassification;
		}

		// Mean calculation
		foreach ($validCluster as $item) {
			$meanLabaItems = $result['laba'][$item]['items'];
			$totalDataLaba = count($meanLabaItems);
			$stdlb = ($totalDataLaba-1);
			$meanLabaValue = array_sum($meanLabaItems) / $totalDataLaba;
			$meanTransaksiItems = $result['transaksi'][$item]['items'];
			$totalDataTransaksi = count($meanTransaksiItems);
			$stdtsksi = ($totalDataTransaksi-1);
			$meanTransaksiValue = array_sum($meanTransaksiItems) / $totalDataTransaksi;
			$meanJumlahItems = $result['jumlah'][$item]['items'];
			$totalDataJumlah = count($meanJumlahItems);
			$stdjmlh = ($totalDataJumlah-1);
			$meanJumlahValue = array_sum($meanJumlahItems) / $totalDataJumlah;

			// Mean results
			$result['laba'][$item]['mean'] = $meanLabaValue;
			$result['transaksi'][$item]['mean'] = $meanTransaksiValue;
			$result['jumlah'][$item]['mean'] = $meanJumlahValue;

			// Standard deviation calculation
			$varianceLaba = 0;
			foreach ($meanLabaItems as $meanOriginValue) {
				$varianceLaba += pow(($meanOriginValue - $meanLabaValue), 2);
			}
			
			$varianceTransaksi = 0;
			foreach ($meanTransaksiItems as $meanOriginValue) {
				$varianceTransaksi += pow(($meanOriginValue - $meanTransaksiValue), 2);
			}

			$varianceJumlah = 0;
			foreach ($meanJumlahItems as $meanOriginValue) {
				$varianceJumlah += pow(($meanOriginValue - $meanJumlahValue), 2);
			}

			// Standard deviation results
			$result['laba'][$item]['std'] = sqrt($varianceLaba / ($stdlb));
			$result['transaksi'][$item]['std'] = sqrt($varianceTransaksi / ($stdtsksi));
			$result['jumlah'][$item]['std'] = sqrt($varianceJumlah / ($stdjmlh));
		}

		return $result;
	}

	private function classifyingCluster(float $c1, float $c2, float $c3): string {
		$group = "c3";

		if ($c1 < $c2 && $c1 < $c3) {
			$group = "c1";
		} else if ($c2 < $c3) {
			$group = "c2";
		}

		return $group;
	}
	

}
