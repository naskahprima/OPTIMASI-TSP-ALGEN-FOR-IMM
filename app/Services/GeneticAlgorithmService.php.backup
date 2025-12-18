<?php

namespace App\Services;

use App\Models\Destinasi;
use App\Models\MatriksJarak;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GeneticAlgorithmService
{
    protected $kromosom;
    protected $maxGen;
    protected $titikAwal;
    protected $crossoverRate;
    protected $mutationRate;
    protected $distanceMatrix = [];
    

    public function __construct($kromosom, $maxGen, $titikAwal, $crossoverRate, $mutationRate)
    {
        $this->kromosom = $kromosom;
        $this->maxGen = $maxGen;
        $this->titikAwal = $titikAwal;
        $this->crossoverRate = $crossoverRate;
        $this->mutationRate = $mutationRate;
        $this->distanceMatrix = $this->getDistanceMatrix();
    }

    protected function getDistanceMatrix()
    {
        // Ambil data dari tabel matriks_jaraks
        // $distances = MatriksJarak::all();
        $distances = DB::table('matriks_jaraks')->get();
        
        $matrix = [];

        foreach ($distances as $row) {
            $matrix[$row->origin_id][$row->destination_id] = $row->distance;
        }

        // dd($matrix);
        return $matrix;

    }

    // public function run()
    // {
    //     $startTime = microtime(true); // Catat waktu mulai
    //     $population = $this->initializePopulation();
    //     $bestFitness = 0;
    //     $bestChromosome = [];

    //     for ($gen = 0; $gen < $this->maxGen; $gen++) {
    //         $fitness = $this->evaluateFitness($population);
    //         [$bestChromosome, $bestFitness] = $this->getBestChromosome($fitness, $population);

    //         // Seleksi
    //         $population = $this->selection($population, $fitness);


    //         // Crossover
    //         $population = $this->crossover($population);

    //         // Mutasi
    //         $population = $this->mutate($population);
    //     }
        

    //     $executionTime = microtime(true) - $startTime; // Hitung waktu eksekusi

    //     // Hitung jarak terbaik dalam kilometer
    //     $totalDistanceInMeters = $this->calculateTotalDistance($bestChromosome);
    //     $totalDistanceInKilometers = $totalDistanceInMeters / 1000; // Konversi ke km

    //     return [
    //         'fitness' => $bestFitness,
    //         'chromosome' => $bestChromosome,
    //         'distance_km' => $totalDistanceInKilometers,
    //         'execution_time' => $executionTime . ' seconds',
    //     ];
    // }

    public function run()
    {
        $startTime = microtime(true);
        $population = $this->initializePopulation();
        $bestFitness = 0;
        $bestChromosome = [];

        for ($gen = 0; $gen < $this->maxGen; $gen++) {
            $fitness = $this->evaluateFitness($population);
            [$bestChromosome, $bestFitness] = $this->getBestChromosome($fitness, $population);
            $population = $this->selection($population, $fitness);
            $population = $this->crossover($population);
            $population = $this->mutate($population);
        }

        $executionTime = microtime(true) - $startTime;

        // Tambahkan titik awal ke akhir rute
        $completeRoute = array_merge($bestChromosome, [(string)$this->titikAwal]);
        
        $totalDistanceInMeters = $this->calculateTotalDistance($bestChromosome);
        $totalDistanceInKilometers = $totalDistanceInMeters / 1000;

        return [
            'fitness' => $bestFitness,
            'chromosome' => $completeRoute, // Gunakan rute yang sudah lengkap dengan titik awal di akhir
            'distance_km' => $totalDistanceInKilometers,
            'execution_time' => $executionTime . ' seconds',
        ];
    }
    


    protected function initializePopulation()
    {
        $destinations = Destinasi::where('id', '!=', $this->titikAwal)
                        ->pluck('id')
                        ->map(fn($id) => (string)$id)
                        ->toArray();

        $population = [];

        for ($i = 0; $i < $this->kromosom; $i++) {
            $chromosome = $destinations;
            shuffle($chromosome);
            array_unshift($chromosome, $this->titikAwal);
            $population[] = $chromosome;
        }

        // dd($population);
        return $population;
    }

    


    protected function evaluateFitness($population)
    {
        $fitness = [];
        foreach ($population as $chromosome) {
            $totalDistance = $this->calculateTotalDistance($chromosome);
            $fitness[] = 1 / ($totalDistance ?: 1); // Hindari pembagian oleh nol
        }
        return $fitness;
    }

    protected function calculateTotalDistance($chromosome)
    {
        $distance = 0;

        for ($i = 0; $i < count($chromosome) - 1; $i++) {
            $origin = $chromosome[$i];
            $destination = $chromosome[$i + 1];
            $distance += $this->distanceMatrix[$origin][$destination] ?? INF;

            if (!isset($this->distanceMatrix[$origin][$destination])) {
                // Log::warning("Missing distance: origin $origin to destination $destination");
                dump("Missing distance: origin $origin to destination $destination");
            }
        }

        // Tambahkan jarak kembali ke titik awal
        $distance += $this->distanceMatrix[end($chromosome)][$chromosome[0]] ?? INF;
        return $distance;
    }


    protected function getBestChromosome($fitness, $population)
    {
        $maxFitnessKey = array_keys($fitness, max($fitness))[0];
        return [$population[$maxFitnessKey], $fitness[$maxFitnessKey]];
    }

    protected function selection($population, $fitness)
    {

        
        $totalFitness = array_sum($fitness);
        $probabilities = array_map(fn($f) => $f / $totalFitness, $fitness);

        $newPopulation = [];
        for ($i = 0; $i < count($population); $i++) {
            $rand = mt_rand() / mt_getrandmax();
            $cumulativeProbability = 0;

            foreach ($population as $key => $chromosome) {
                $cumulativeProbability += $probabilities[$key];
                if ($rand <= $cumulativeProbability) {
                    $newPopulation[] = $chromosome;
                    break;
                }
            }
        }

        return $newPopulation;
    }

    protected function crossover($population)
    {

        
        // dd($population);
        $newPopulation = [];



        for ($i = 0; $i < count($population) / 2; $i++) {
            if (mt_rand() / mt_getrandmax() <= $this->crossoverRate) {
                $parent1 = $population[$i];
                $parent2 = $population[$i + 1];
                $offspring = $this->orderedCrossover($parent1, $parent2);
                $newPopulation[] = $offspring[0];
                $newPopulation[] = $offspring[1];
            } else {
                $newPopulation[] = $population[$i];
                $newPopulation[] = $population[$i + 1];
            }
        }

        return $newPopulation;
    }

    protected function orderedCrossover($parent1, $parent2)
    {
        $size = count($parent1);
        $start = rand(0, $size - 1);
        $end = rand($start, $size - 1);

        $child1 = array_fill(0, $size, null);
        $child2 = array_fill(0, $size, null);

        // Copy segment
        for ($i = $start; $i <= $end; $i++) {
            $child1[$i] = $parent1[$i];
            $child2[$i] = $parent2[$i];
        }

        // Fill in remaining
        $child1 = $this->fillRemainingGenes($child1, $parent2);
        $child2 = $this->fillRemainingGenes($child2, $parent1);

        return [$child1, $child2];
    }

    protected function fillRemainingGenes($child, $parent)
    {
        $size = count($child);
        $currentIndex = 0;

        foreach ($parent as $gene) {
            if (!in_array($gene, $child)) {
                while ($child[$currentIndex] !== null) {
                    $currentIndex++;
                }
                $child[$currentIndex] = $gene;
            }
        }

        return $child;
    }

    protected function mutate($population)
    {
        foreach ($population as &$chromosome) {
            if (mt_rand() / mt_getrandmax() <= $this->mutationRate) {
                $index1 = rand(1, count($chromosome) - 1);
                $index2 = rand(1, count($chromosome) - 1);

                // Swap two points
                $temp = $chromosome[$index1];
                $chromosome[$index1] = $chromosome[$index2];
                $chromosome[$index2] = $temp;
            }
        }

        return $population;
    }
}
