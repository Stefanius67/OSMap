<?php
declare(strict_types=1);

namespace SKien\OSMap;

/**
 * Class describing single step of calculated route.
 *
 * @package OSMap
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class OSMapOpenRouteStep
{
    /** @var ?array<string,mixed>  step of a calculated route  */
    protected ?array $aStep = null;

    /**
     * Create new instance.
     * @param ?array<string,mixed> $aStep
     */
    public function __construct(?array $aStep = null)
    {
        $this->aStep = $aStep;
    }

    /**
     * Init instance with new step data from array.
     * @param ?array<string,mixed> $aStep
     */
    public function fromArray(?array $aStep = null) : void
    {
        $this->aStep = $aStep;
    }

    /**
     * Get the distance for this step in in the specified unit.
     * @return float
     */
    public function getDistance() : float
    {
        $fltValue = 0.0;
        if ($this->aStep !== null && isset($this->aStep['distance'])) {
            $fltValue = floatval($this->aStep['distance']);
        }
        return $fltValue;
    }

    /**
     * Get the duration for this step in seconds.
     * @return float
     */
    public function getDuration() : float
    {
        $fltValue = 0.0;
        if ($this->aStep !== null && isset($this->aStep['duration'])) {
            $fltValue = floatval($this->aStep['duration']);
        }
        return $fltValue;
    }

    /**
     * Get the instruction for this step.
     * @return string
     */
    public function getInstruction() : string
    {
        $strValue = '';
        if ($this->aStep !== null && isset($this->aStep['instruction'])) {
            $strValue = (string)$this->aStep['instruction'];
        }
        return $strValue;
    }

    /**
     * Get the name for this step.
     * @return string
     */
    public function getName() : string
    {
        $strValue = '';
        if ($this->aStep !== null && isset($this->aStep['name'])) {
            $strValue = (string)$this->aStep['name'];
        }
        return $strValue;
    }
}
