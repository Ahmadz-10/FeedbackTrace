"""Cosine and threshold evaluation utilities only; not used to group production findings."""
import math
from dataclasses import dataclass
from typing import Sequence
def cosine_similarity(a:Sequence[float],b:Sequence[float])->float:
    if len(a)!=len(b):raise ValueError(f"Dimensi vector berbeda: {len(a)} != {len(b)}")
    na=math.sqrt(sum(x*x for x in a));nb=math.sqrt(sum(y*y for y in b))
    if na==0 or nb==0:return 0.0
    return max(-1.0,min(1.0,sum(x*y for x,y in zip(a,b))/(na*nb)))
@dataclass(frozen=True)
class ThresholdReport:
    threshold:float
    precision:float
    recall:float
    f1:float
    true_positive:int
    false_positive:int
    false_negative:int
    true_negative:int
def evaluate_threshold(pairs:Sequence[tuple[float,bool]],threshold:float)->ThresholdReport:
    tp=sum(1 for s,y in pairs if s>=threshold and y);fp=sum(1 for s,y in pairs if s>=threshold and not y)
    fn=sum(1 for s,y in pairs if s<threshold and y);tn=sum(1 for s,y in pairs if s<threshold and not y)
    precision=tp/(tp+fp) if tp+fp else 1.0;recall=tp/(tp+fn) if tp+fn else 0.0
    f1=2*precision*recall/(precision+recall) if precision+recall else 0.0
    return ThresholdReport(threshold,precision,recall,f1,tp,fp,fn,tn)
def calibrate_threshold(pairs:Sequence[tuple[float,bool]],*,min_precision:float=.95)->ThresholdReport|None:
    if not any(y for _,y in pairs):return None
    for threshold in sorted({s for s,_ in pairs}):
        report=evaluate_threshold(pairs,threshold)
        if report.precision>=min_precision and report.true_positive>0:return report
    return None
